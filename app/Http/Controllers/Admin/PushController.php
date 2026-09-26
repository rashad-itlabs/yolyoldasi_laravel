<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Notification;
use App\Models\PushMessage;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Əl ilə push göndərmə — hamıya və ya bir istifadəçiyə.
 *
 * Göndəriş iki yerə yazılır: `push_messages` panelin tarixçəsidir,
 * `notifications` isə hər alıcı üçün ayrı sətirdir və tətbiq onu
 * `GET /api/v1/notifications` ilə oxuyur. Push özü bunların üstünə gəlir —
 * çatmasa da bildiriş siyahıda qalır.
 */
class PushController extends Controller
{
    /** Forma seçimləri — açarlar `config('onesignal.types')` ilə eynidir. */
    public const TYPES = [
        'adminMessage'   => ['Xidmət elanı', 'Yalnız push bildirişlərini tamamilə söndürənlərə getmir.'],
        'adminMarketing' => ['Kampaniya', 'Reklam bildirişlərini söndürənlərə getmir.'],
    ];

    public function index(OneSignalService $push)
    {
        $reach = [];

        foreach (array_keys(self::TYPES) as $type) {
            $reach[$type] = $push->reach($type);
        }

        return view('admin.push.index', [
            'types' => self::TYPES,
            'reach' => $reach,
            // Siyahı kiçik olduğu üçün hamısı birdəfəlik gətirilir; istifadəçi
            // sayı artanda burada axtarışa keçmək lazım olacaq.
            'users' => User::orderBy('full_name')->get(['id', 'full_name', 'phone', 'is_blocked']),
            'sent' => PushMessage::with(['admin', 'user'])
                ->withCount(['notifications as read_count' => fn ($query) => $query->whereNotNull('read_at')])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(Request $request, OneSignalService $push)
    {
        $data = $request->validate([
            'target'  => ['required', 'in:all,user'],
            'user_id' => ['required_if:target,user', 'nullable', 'integer', 'exists:users,id'],
            'type'    => ['required', 'in:'.implode(',', array_keys(self::TYPES))],
            // Tətbiqin bildiriş siyahısı mətni tam göstərir, telefon isə
            // bildirişi açanda bütün mətni göstərir. Yuxarı hədd qalır, çünki
            // push-un ümumi ölçüsü Apple və Google tərəfindən ~4 KB ilə
            // məhdudlaşır; 1000 simvol kirill hərfləri ilə də rahat sığır.
            'heading' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:1000'],
        ], [], [
            'heading' => 'başlıq',
            'content' => 'mətn',
            'user_id' => 'istifadəçi',
        ]);

        $user = $data['target'] === 'user'
            ? User::findOrFail($data['user_id'])
            : null;

        // Alıcılar bir dəfə hesablanır: həm bildiriş sətirləri, həm də push
        // eyni siyahıya görə gedir.
        $users = $push->recipients($data['type'], $user);

        if ($users->isEmpty()) {
            return back()->withInput()->withErrors(['heading' => $user
                ? 'Bu istifadəçi həmin növ bildirişləri söndürüb — heç nə göndərilmədi.'
                : 'Alıcı yoxdur: bu növ bildirişi bütün istifadəçilər söndürüb.',
            ]);
        }

        $message = DB::transaction(fn () => $this->record($data, $user, $users));

        $result = $push->deliverTo($users, $data['heading'], $data['content'], $data['type']);

        $message->forceFill([
            'delivered' => $result['ok'],
            'error'     => $result['error'],
        ])->save();

        AdminActivityLog::record('push.sent', $message, [
            'target'     => $data['target'],
            'type'       => $data['type'],
            'heading'    => $data['heading'],
            'recipients' => $users->count(),
            'delivered'  => $result['ok'],
            'error'      => $result['error'],
        ]);

        $who = $user ? $user->full_name.' hesabına' : $users->count().' istifadəçiyə';

        $response = back()->with(
            'success',
            "Bildiriş {$who} yazıldı — tətbiqin bildiriş siyahısında görünür."
            .($result['ok'] ? ' Push '.($result['aliases'] + $result['devices']).' ünvana göndərildi.' : '')
        );

        // Sətirlər yazılıb, amma push getməyib: hər ikisini göstəririk ki,
        // admin nəyin baş verdiyini yarımçıq bilməsin.
        if (! $result['ok'] || $result['error']) {
            $response->withErrors(['push' => $result['ok']
                ? 'Push qismən çatdı: '.$result['error']
                : 'Push göndərilmədi: '.($result['error'] ?? 'naməlum səbəb'),
            ]);
        }

        return $response;
    }

    /**
     * Göndəriş sətri və hər alıcı üçün bildiriş sətri.
     *
     * Bildirişlər `NotificationService`-dən yox, toplu `insert` ilə yazılır:
     * o servis hər sətir üçün ayrıca push göndərir, burada isə push bütün
     * alıcılara bir sorğu ilə gedir — əks halda min alıcı min HTTP sorğusu
     * demək olardı.
     */
    private function record(array $data, ?User $user, $users): PushMessage
    {
        $message = PushMessage::create([
            'admin_id'   => Auth::guard('admin')->id(),
            'type'       => $data['type'],
            'target'     => $data['target'],
            'user_id'    => $user?->id,
            'heading'    => $data['heading'],
            'content'    => $data['content'],
            'recipients' => $users->count(),
        ]);

        $now     = now();
        $payload = json_encode([
            'heading' => $data['heading'],
            'content' => $data['content'],
        ], JSON_UNESCAPED_UNICODE);

        $rows = $users->map(fn ($recipient) => [
            'user_id'         => $recipient->id,
            'type'            => $data['type'],
            'push_message_id' => $message->id,
            // `insert` model cast-larını keçir, ona görə JSON əl ilə yığılır
            // və `created_at` əl ilə qoyulur.
            'payload'         => $payload,
            'created_at'      => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            Notification::insert($chunk);
        }

        return $message;
    }
}

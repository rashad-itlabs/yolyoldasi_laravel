<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private MessageService $messages)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['booking'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        // Qarşı tərəfi bir sorğu ilə yükləyirik — N+1 olmasın.
        $otherIds = $conversations->map(fn ($c) => $c->booking->passenger_id === $user->id
            ? $c->booking->driver_id
            : $c->booking->passenger_id);

        $others = User::with('stats')->whereIn('id', $otherIds)->get()->keyBy('id');

        $conversations->each(function ($c) use ($user, $others) {
            $otherId = $c->booking->passenger_id === $user->id
                ? $c->booking->driver_id
                : $c->booking->passenger_id;

            $c->other_user   = $others->get($otherId);
            $c->unread_count = $c->pivot->unread_count;
        });

        return ConversationResource::collection($conversations);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $this->messages->recipientId($conversation, $request->user()); // icazə yoxlaması

        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
        ]);

        $message = $this->messages->send($conversation, $request->user(), $data['text']);

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $this->messages->recipientId($conversation, $request->user());
        $this->messages->markRead($conversation, $request->user());

        return response()->json(['message' => 'Oxundu kimi işarələndi.']);
    }

    public function unreadCount(Request $request)
    {
        return response()->json([
            'unread_total' => (int) $request->user()->conversations()->sum('unread_count'),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::ordered()->get();

        return view('admin.content.faqs', compact('faqs'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            // Yeni sual sona düşür — mövcud sıralama pozulmasın.
            $data['sort_order'] = (int) Faq::max('sort_order') + 10;

            $faq = Faq::create($data);

            AdminActivityLog::record('faq.created', $faq, ['question' => $faq->question]);
        });

        return back()->with('success', 'Sual əlavə edildi.');
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($faq, $data) {
            $faq->update($data);

            AdminActivityLog::record('faq.updated', $faq, ['question' => $faq->question]);
        });

        return back()->with('success', 'Sual yeniləndi.');
    }

    public function destroy(Faq $faq)
    {
        DB::transaction(function () use ($faq) {
            AdminActivityLog::record('faq.deleted', $faq, ['question' => $faq->question]);
            $faq->delete();
        });

        return back()->with('success', 'Sual silindi.');
    }

    public function toggle(Faq $faq)
    {
        DB::transaction(function () use ($faq) {
            $faq->update(['is_active' => ! $faq->is_active]);

            AdminActivityLog::record(
                $faq->is_active ? 'faq.activated' : 'faq.deactivated',
                $faq,
                ['question' => $faq->question]
            );
        });

        return back()->with('success', $faq->is_active ? 'Sual göstərilir.' : 'Sual gizlədildi.');
    }

    /**
     * Qonşu sual ilə yerini dəyişir.
     *
     * sort_order-lər 10-luq addımlarla verilir, amma bərabər dəyərlər də ola
     * bilər (əl ilə redaktə), ona görə sadəcə artırmaq/azaltmaq yerinə iki
     * sətrin dəyəri birbaşa dəyişdirilir.
     */
    public function move(Request $request, Faq $faq)
    {
        $direction = $request->input('direction');

        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        DB::transaction(function () use ($faq, $direction) {
            // Sıralama (sort_order, id) cütü ilə müəyyən olunur — qonşu da
            // həmin cütə görə seçilməlidir, yoxsa bərabər sort_order-lərdə
            // "yuxarı" düyməsi özündən sonrakı sətri tuta bilər.
            $neighbour = Faq::query()
                ->where(fn ($q) => $direction === 'up'
                    ? $q->where('sort_order', '<', $faq->sort_order)
                        ->orWhere(fn ($t) => $t->where('sort_order', $faq->sort_order)->where('id', '<', $faq->id))
                    : $q->where('sort_order', '>', $faq->sort_order)
                        ->orWhere(fn ($t) => $t->where('sort_order', $faq->sort_order)->where('id', '>', $faq->id))
                )
                ->when($direction === 'up',
                    fn ($q) => $q->orderByDesc('sort_order')->orderByDesc('id'),
                    fn ($q) => $q->orderBy('sort_order')->orderBy('id')
                )
                ->first();

            if (! $neighbour) {
                return;
            }

            $mine = $faq->sort_order;
            $theirs = $neighbour->sort_order;

            // Bərabər olanda sadə dəyişmə heç nə etməzdi — aralarını açırıq.
            if ($mine === $theirs) {
                $mine = $direction === 'up' ? $theirs - 1 : $theirs + 1;
            }

            $faq->update(['sort_order' => $theirs]);
            $neighbour->update(['sort_order' => $mine]);
        });

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question'  => ['required', 'string', 'max:300'],
            'answer'    => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // İşarələnməmiş checkbox sorğuya heç düşmür — açıq şəkildə false yazırıq.
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

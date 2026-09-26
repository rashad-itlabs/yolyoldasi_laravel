<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * İstifadə qaydaları, məxfilik siyasəti və haqqımızda səhifələri.
 *
 * Yeni səhifə yaratmaq nəzərdə tutulmayıb — hər slug üçün saytda ayrıca
 * marşrut və şablon var, ona görə siyahı sabitdir (Page::SLUGS).
 */
class PageController extends Controller
{
    public function index()
    {
        $pages = Page::orderByRaw("FIELD(slug,'terms','privacy','about')")->get();

        return view('admin.content.pages', compact('pages'));
    }

    public function edit(Page $page)
    {
        return view('admin.content.page-edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title'              => ['required', 'string', 'max:190'],
            'lead'               => ['nullable', 'string', 'max:1000'],
            'body'               => ['required', 'string'],
            'meta_description'   => ['nullable', 'string', 'max:300'],
            'content_updated_at' => ['nullable', 'date'],
            'is_published'       => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($page, $data) {
            $changed = [];

            foreach (['title', 'lead', 'meta_description'] as $field) {
                if ((string) $page->{$field} !== (string) ($data[$field] ?? '')) {
                    $changed[$field] = ['from' => $page->{$field}, 'to' => $data[$field] ?? null];
                }
            }

            // Mətnin özü jurnala yazılmır — uzun HTML jurnalı oxunmaz edir,
            // ona görə yalnız ölçü fərqi saxlanılır.
            if ($page->body !== $data['body']) {
                $changed['body'] = [
                    'from_length' => mb_strlen((string) $page->body),
                    'to_length'   => mb_strlen($data['body']),
                ];
            }

            $page->update([
                'title'              => $data['title'],
                'lead'               => $data['lead'] ?? null,
                'body'               => $data['body'],
                'meta_description'   => $data['meta_description'] ?? null,
                'content_updated_at' => $data['content_updated_at'] ?? null,
                'is_published'       => (bool) ($data['is_published'] ?? false),
            ]);

            AdminActivityLog::record('page.updated', $page, [
                'slug'    => $page->slug,
                'changes' => $changed,
            ]);
        });

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', "«{$page->title}» yeniləndi.");
    }
}

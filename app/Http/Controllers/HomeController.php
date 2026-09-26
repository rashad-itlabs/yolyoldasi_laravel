<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;

class HomeController extends Controller
{
    public function home()
    {
        return view('frontend.home', [
            'faqs' => Faq::active()->ordered()->get(),
        ]);
    }

    public function contact()
    {
        return view('frontend.contact');
    }

    public function privacy()
    {
        return $this->page('privacy');
    }

    public function term()
    {
        return $this->page('terms');
    }

    public function about()
    {
        return $this->page('about');
    }

    public function accountDeletion()
    {
        return view('frontend.account-deletion');
    }

    /**
     * Hüquqi və məlumat səhifələri eyni şablonu paylaşır — fərq yalnız
     * bazadakı sətirdir. Yayımdan çıxarılmış səhifə 404 verir.
     */
    private function page(string $slug)
    {
        $page = Page::where('slug', $slug)->published()->firstOrFail();

        return view('frontend.legal', compact('page'));
    }
}

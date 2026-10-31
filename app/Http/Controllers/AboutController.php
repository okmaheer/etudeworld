<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\InfoPage;

class AboutController extends Controller
{
    public function show()
    {
        $page = InfoPage::where('slug', 'about-us')->first();

        return view('about', [
            'page'         => $page,
            'extra'        => $page && $page->is_published && trim(strip_tags((string) $page->body, '<img><iframe><table>')) !== '' ? $page->body : null,
            'achievements' => Achievement::where('is_published', true)->orderBy('sort_order')->orderBy('id')->get(),
            'social'       => \App\Models\SiteSetting::group('site')['social'] ?? [],
            'contact'      => \App\Models\SiteSetting::contact(),
        ]);
    }
}

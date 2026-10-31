<?php

namespace App\Http\Controllers;

use App\Models\InfoPage;

class InfoPageController extends Controller
{
    public function show(string $slug)
    {
        $page = InfoPage::where('slug', $slug)->first();
        $system = request()->route()->getName() !== 'pages.show';   // About, Contact, Privacy, Terms

        if ($system) {
            // Missing, hidden or still empty: the friendly "Coming soon" screen.
            if (! $page || ! $page->is_system || ! $page->is_live) {
                return response()->view('coming-soon', ['title' => $page->title ?? ucwords(str_replace('-', ' ', $slug))]);
            }
        } elseif (! $page || $page->is_system || ! $page->is_live) {
            abort(404);
        }

        return view('pages.show', compact('page'));
    }
}

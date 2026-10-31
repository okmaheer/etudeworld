<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Temporary page shown for sections that are not built yet.
     */
    public function comingSoon(Request $request)
    {
        return view('coming-soon', [
            'title' => $request->route('title') ?? 'Coming soon',
        ]);
    }
}

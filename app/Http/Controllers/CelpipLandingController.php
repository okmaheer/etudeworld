<?php

namespace App\Http\Controllers;

use App\Models\CelpipTest;

/** /celpip – the "CELPIP" page: a hero, then CELPIP General and CELPIP LS. */
class CelpipLandingController extends Controller
{
    public function __invoke()
    {
        $count = fn (string $format) => CelpipTest::where('format', $format)->visible()->count();

        return view('celpip.landing', [
            'generalCount' => $count('general'),
            'lsCount'      => $count('ls'),
        ]);
    }
}

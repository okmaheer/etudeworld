<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\TestSeo;
use App\Models\ToeflTest;
use Illuminate\Http\Request;

/** Saves the "Search results (SEO)" box of a test: the full mock test and each section. */
class TestSeoController extends Controller
{
    private const PAGES = ['mock', 'reading', 'listening', 'writing', 'speaking'];

    public function save(Request $request, string $exam, int $test)
    {
        $model = ['ielts' => IeltsTest::class, 'celpip' => CelpipTest::class, 'toefl' => ToeflTest::class][$exam] ?? abort(404);
        $model::findOrFail($test);

        $request->validate([
            'seo.*.meta_title'       => ['nullable', 'string', 'max:255'],
            'seo.*.meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (self::PAGES as $page) {
            $f = $request->input("seo.$page");
            if (! is_array($f)) {
                continue;
            }
            $title = trim((string) ($f['meta_title'] ?? '')) ?: null;
            $desc = trim((string) ($f['meta_description'] ?? '')) ?: null;
            $index = ! empty($f['indexable']);
            if (! $title && ! $desc && ! $index) {
                TestSeo::where(['exam' => $exam, 'test_id' => $test, 'page' => $page])->delete();
            } else {
                TestSeo::updateOrCreate(['exam' => $exam, 'test_id' => $test, 'page' => $page],
                    ['meta_title' => $title, 'meta_description' => $desc, 'indexable' => $index]);
            }
        }

        return back()->with('success', 'Search settings were saved.');
    }
}

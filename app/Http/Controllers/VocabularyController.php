<?php

namespace App\Http\Controllers;

use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\VocabWord;
use Illuminate\Support\Facades\Auth;

/** What students see of the Vocabulary Builder: the printable word list and the practice cards. */
class VocabularyController extends Controller
{
    /** The approved words of a test section (null when students may not see this test or there are none). */
    public static function words(IeltsTest $test, string $module)
    {
        if (! in_array($module, ['reading', 'listening'], true) || ! $test->moduleReady($module) || $test->isLocked()) return collect();
        if ($test->status !== 'active' && ! Auth::check()) return collect();

        return VocabWord::where('ielts_test_id', $test->id)->where('module', $module)->where('approved', true)->orderBy('id')->get();
    }

    /** The approved words of a CELPIP Listening / Reading test. */
    public static function celpipWords(CelpipTest $test, string $module)
    {
        if (! in_array($module, ['reading', 'listening'], true) || ! $test->moduleReady($module) || $test->isLocked()) return collect();
        if ($test->status !== 'active' && ! Auth::check()) return collect();

        return VocabWord::where('exam', 'celpip')->where('celpip_test_id', $test->id)->where('module', $module)->where('approved', true)->orderBy('id')->get();
    }

    /** Results-page card for a CELPIP test (same card as IELTS). */
    public static function celpipSummary(CelpipTest $test, string $module): ?array
    {
        $words = static::celpipWords($test, $module);
        if ($words->isEmpty()) return null;
        $key = ['module' => $module, 'test' => $test->id];

        return [
            'count' => $words->count(), 'samples' => $words->take(5)->pluck('word')->all(), 'langs' => VocabWord::LANGUAGES,
            'data' => route('vocab.celpip.data', $key), 'script' => asset('js/vocab-pdf.js') . '?v=2', 'practise' => route('vocab.celpip.test', $key),
        ];
    }

    /** A short summary for the results page. */
    public static function summary(IeltsTest $test, string $module): ?array
    {
        $words = static::words($test, $module);
        if ($words->isEmpty()) return null;
        $key = ['module' => $module, 'test' => $test->id];

        return [
            'count'    => $words->count(),
            'samples'  => $words->take(5)->pluck('word')->all(),
            'langs'    => VocabWord::LANGUAGES,
            'pdf'      => route('ielts.vocab.print', $key),
            'data'     => route('ielts.vocab.data', $key),
            'script'   => asset('js/vocab-pdf.js') . '?v=1',
            'practise' => route('vocab.test', $key),
        ];
    }

    /** Printable list – the browser's "Save as PDF" turns it into the PDF (works for every language and script). */
    public function print(string $module, IeltsTest $test)
    {
        return view('ielts.vocab-print', $this->data0($test, $module));
    }

    /** The words as JSON – the browser turns them into the downloaded PDF. */
    public function data(string $module, IeltsTest $test)
    {
        $d = $this->data0($test, $module);
        $lang = $d['lang'];

        return response()->json([
            'title'    => $test->name . ' – ' . ucfirst($module) . ' vocabulary',
            'subtitle' => $d['words']->count() . ' words and phrases · Etude World',
            'langName' => $lang ? $d['languages'][$lang] : '',
            'rtl'      => in_array($lang, ['ar', 'ur'], true),
            'filename' => \Illuminate\Support\Str::slug($test->name . ' ' . $module) . '-vocabulary' . ($lang ? "-$lang" : '') . '.pdf',
            'words'    => $d['words']->map(fn ($w) => ['word' => $w->word, 'type' => $w->type, 'meaning' => $w->meaning, 'synonyms' => implode(', ', $w->synonyms ?? []), 'example' => $w->example, 'tr' => $lang ? ($w->translations[$lang] ?? '') : ''])->values(),
        ]);
    }

    public function practise(string $module, IeltsTest $test)
    {
        $d = $this->data0($test, $module);
        $d['cards'] = $d['words']->map(fn ($w) => ['word' => $w->word, 'type' => $w->type, 'meaning' => $w->meaning, 'synonyms' => implode(', ', $w->synonyms ?? []), 'example' => $w->example, 'tr' => $d['lang'] ? ($w->translations[$d['lang']] ?? '') : ''])->values()->all();

        return view('ielts.vocab-practise', $d);
    }

    private function data0(IeltsTest $test, string $module): array
    {
        $words = static::words($test, $module);
        abort_if($words->isEmpty(), 404);
        $lang = request('lang');

        return ['test' => $test, 'module' => $module, 'words' => $words, 'lang' => isset(VocabWord::LANGUAGES[$lang]) ? $lang : null, 'languages' => VocabWord::LANGUAGES];
    }
}

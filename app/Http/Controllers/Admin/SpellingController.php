<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpellingWord;
use App\Support\SpellingCollector;
use Illuminate\Http\Request;

/** Admin review of the Important Spellings collected from IELTS Listening Part 1 and Part 4. */
class SpellingController extends Controller
{
    public function index()
    {
        return view('admin.vocab.spellings', ['words' => SpellingWord::orderBy('category')->orderBy('word')->get()]);
    }

    public function rescan()
    {
        SpellingCollector::rescanAll();

        return back()->with('success', 'All Listening tests were scanned again.');
    }

    /** Put every word into its group again, automatically (free, no AI). */
    public function sortAll()
    {
        $n = 0;
        foreach (SpellingWord::all() as $w) { $w->update(['category' => SpellingCollector::guess($w->word)]); $n++; }

        return back()->with('success', $n . ' words were sorted into groups.');
    }

    public function add(Request $request)
    {
        $d = $request->validate(['word' => 'required|string|max:120']);
        $w = trim($d['word']);
        SpellingWord::firstOrCreate(['wkey' => mb_strtolower($w)], ['word' => $w, 'category' => SpellingCollector::guess($w), 'shown' => false, 'tests' => []]);

        return back()->with('success', 'The word was added.');
    }

    /** Save every row: show / hide ticks, category, alternative spellings and deletes. */
    public function save(Request $request)
    {
        $rows = (array) $request->input('w', []);
        foreach (SpellingWord::all() as $w) {
            $r = $rows[$w->id] ?? null;
            if (! is_array($r)) continue;
            if (! empty($r['delete'])) { $w->delete(); continue; }
            $w->update([
                'category' => mb_substr(trim((string) ($r['category'] ?? '')) ?: 'Nouns', 0, 60),
                'alternatives' => array_values(array_filter(array_map('trim', explode(',', (string) ($r['alternatives'] ?? ''))))),
                'shown' => ! empty($r['shown']),
            ]);
        }

        return back()->with('success', 'Saved.');
    }
}

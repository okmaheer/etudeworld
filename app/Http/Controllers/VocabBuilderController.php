<?php

namespace App\Http\Controllers;

use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\StudentVocabProgress;
use App\Models\VocabTopic;
use App\Models\VocabWord;
use App\Support\VocabLists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Vocabulary Builder pages for students: exam → skill → one list (an IELTS / CELPIP test section, or a Writing / Speaking topic). */
class VocabBuilderController extends Controller
{
    /** The skills of one exam (IELTS or CELPIP). */
    public function exam(string $exam)
    {
        abort_unless(isset(VocabLists::EXAMS[$exam]), 404);

        return view('vocab.exam', compact('exam'));
    }

    /** Listening / Reading: every test that has approved words, split by format. */
    public function skill(string $exam, string $module)
    {
        abort_unless(isset(VocabLists::EXAMS[$exam]) && in_array($module, ['listening', 'reading'], true), 404);
        $model = $exam === 'celpip' ? CelpipTest::class : IeltsTest::class;
        $titles = $exam === 'celpip' ? ['general' => 'CELPIP General', 'ls' => 'CELPIP LS'] : ['academic' => 'IELTS Academic', 'gt' => 'IELTS General Training'];
        $groups = [];
        foreach ($model::all()->sortBy(fn ($t) => (int) preg_replace('/\D/', '', $t->moduleLabel())) as $t) {
            $list = $exam === 'celpip' ? VocabLists::celpip($t, $module) : VocabLists::ielts($t, $module);
            if ($list['words']->isEmpty()) continue;
            $groups[$titles[$t->format] ?? 'Tests'][] = ['label' => $t->moduleLabel(), 'url' => $list['show'], 'count' => $list['words']->count()];
        }

        return view('vocab.skill', compact('exam', 'module', 'groups'));
    }

    /** Important spellings of IELTS Listening (free for everyone). */
    public function spellings()
    {
        $words = \App\Models\SpellingWord::where('shown', true)->orderBy('word')->get()
            ->map(fn ($w) => ['w' => $w->word, 'alt' => (array) $w->alternatives, 'cat' => $w->category, 'n' => max(1, count((array) $w->tests))])->values();

        return view('vocab.spellings', compact('words'));
    }

    /** Writing / Speaking: the topics that have approved words. */
    public function topics(string $exam, string $skill)
    {
        abort_unless(isset(VocabLists::EXAMS[$exam]) && isset(VocabTopic::SKILLS[$skill]), 404);
        $items = [];
        foreach (VocabTopic::where('exam', $exam)->where('skill', $skill)->orderBy('name')->get() as $t) {
            $list = VocabLists::topic($t);
            if ($list['words']->isNotEmpty()) $items[] = ['label' => $t->name, 'url' => $list['show'], 'count' => $list['words']->count()];
        }

        return view('vocab.topics', compact('exam', 'skill', 'items'));
    }

    /** The list asked for by the address (404 when it has no words for students). */
    private function resolve(Request $request): array
    {
        $r = $request->route();
        $kind = $r->defaults['kind'] ?? 'ielts';
        if ($kind === 'topic') {
            $t = VocabTopic::where(['exam' => $r->parameter('exam'), 'skill' => $r->parameter('skill'), 'slug' => $r->parameter('slug')])->first();
            abort_unless($t, 404);
            $list = VocabLists::topic($t);
        } else {
            $module = $r->parameter('module');
            abort_unless(in_array($module, ['listening', 'reading'], true), 404);
            $id = (int) $r->parameter('test');
            $t = $kind === 'celpip' ? CelpipTest::findOrFail($id) : IeltsTest::findOrFail($id);
            $list = $kind === 'celpip' ? VocabLists::celpip($t, $module) : VocabLists::ielts($t, $module);
        }
        abort_if($list['words']->isEmpty(), 404);

        return $list;
    }

    private function wordRows($words)
    {
        return $words->map(fn ($w) => ['id' => $w->id, 'word' => $w->word, 'type' => $w->type, 'meaning' => $w->meaning, 'synonyms' => implode(', ', $w->synonyms ?? []), 'example' => $w->example, 'tr' => $w->translations ?? []])->values();
    }

    public function show(Request $request)
    {
        $list = $this->resolve($request);
        // Everyone can read the words (Google too). Only signed-in students get their progress saved.
        $sid = Auth::guard('student')->id();
        $known = [];
        if ($sid) {
            // Opening a list adds it to the student's portal ("My vocabulary").
            $row = StudentVocabProgress::firstOrCreate(['student_id' => $sid, 'list_key' => $list['key']], ['known' => [], 'module' => $list['skill'], 'ielts_test_id' => $list['kind'] === 'test' && $list['exam'] === 'ielts' ? $list['id'] : null]);
            $known = $row->known ?? [];
        } else {
            // If they log in from the page, bring them back to exactly this page.
            $request->session()->put('student.intended', $request->fullUrl());
        }
        [$prev, $next] = VocabLists::neighbours($list);

        return view('vocab.test', [
            'list' => $list, 'prev' => $prev, 'next' => $next, 'languages' => VocabWord::LANGUAGES,
            'known' => (object) array_fill_keys($known, 1), 'loggedIn' => (bool) $sid, 'words' => $this->wordRows($list['words']),
        ]);
    }

    public function exercises(Request $request)
    {
        $list = $this->resolve($request);

        return view('vocab.exercises', [
            'list' => $list,
            'words' => $list['words']->map(fn ($w) => ['word' => $w->word, 'meaning' => $w->meaning, 'example' => $w->example])->values(),
        ]);
    }

    /** The words as JSON – the browser turns them into the downloaded PDF. */
    public function data(Request $request)
    {
        $list = $this->resolve($request);
        $lang = isset(VocabWord::LANGUAGES[$request->query('lang')]) ? $request->query('lang') : null;

        return response()->json([
            'title' => $list['label'] . ' – ' . $list['skillLabel'] . ' vocabulary',
            'subtitle' => $list['words']->count() . ' words and phrases · Etude World',
            'langName' => $lang ? VocabWord::LANGUAGES[$lang] : '',
            'rtl' => in_array($lang, ['ar', 'ur'], true),
            'filename' => Str::slug($list['label'] . ' ' . $list['skill']) . '-vocabulary' . ($lang ? "-$lang" : '') . '.pdf',
            'words' => $list['words']->map(fn ($w) => ['word' => $w->word, 'type' => $w->type, 'meaning' => $w->meaning, 'synonyms' => implode(', ', $w->synonyms ?? []), 'example' => $w->example, 'tr' => $lang ? ($w->translations[$lang] ?? '') : ''])->values(),
        ]);
    }

    /** Saves the learned words of a signed-in student (called by the word list page). */
    public function saveProgress(Request $request)
    {
        $student = Auth::guard('student')->user();
        abort_unless($student, 401);
        $list = $this->resolve($request);
        $ids = collect($request->input('known', []))->map(fn ($i) => (int) $i)->unique()->values()->take(2000)->all();
        StudentVocabProgress::updateOrCreate(
            ['student_id' => $student->id, 'list_key' => $list['key']],
            ['known' => $ids, 'module' => $list['skill'], 'ielts_test_id' => $list['kind'] === 'test' && $list['exam'] === 'ielts' ? $list['id'] : null]
        );

        return response()->json(['saved' => true]);
    }
}

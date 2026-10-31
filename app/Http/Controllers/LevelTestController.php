<?php

namespace App\Http\Controllers;

use App\Models\LevelTest;
use Illuminate\Http\Request;

/** Public English Level Test (CBT-style page, marked on the server) */
class LevelTestController extends Controller
{
    /** Landing page: one card for every active level test */
    public function index()
    {
        $tests = LevelTest::where('status', 'active')->latest()->get();
        foreach ($tests as $t) {
            $t->count = $t->questionCount();
        }

        return view('level.landing', compact('tests'));
    }

    public function show(string $slug)
    {
        $test = LevelTest::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $sections = $test->sections()->with('questions')->get()->keyBy('number');
        $n = 0;
        $numbers = [];
        foreach ([1, 2, 3] as $i) {
            foreach ($sections->get($i)?->questions ?? [] as $q) {
                $numbers[$q->id] = ++$n;
            }
        }

        return view('level.exam', compact('test', 'sections', 'numbers') + ['total' => $n]);
    }

    public function score(Request $request, string $slug)
    {
        $test = LevelTest::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $answers = (array) $request->input('answers', []);
        $score = 0;
        $review = [];
        $n = 0;
        foreach ($test->sections as $section) {
            foreach ($section->questions as $q) {
                $n++;
                $given = isset($answers[$q->id]) ? (string) $answers[$q->id] : '';
                $ok = $q->isCorrect($given);
                $score += $ok ? 1 : 0;
                $yours = $given === '' ? '' : ($q->type === 'mcq' ? chr(97 + (int) $given) . ') ' . ($q->options[(int) $given] ?? '') : $given);
                $review[] = ['n' => $n, 'id' => $q->id, 'section' => $section->number, 'prompt' => $q->prompt, 'ok' => $ok, 'yours' => $yours, 'answer' => $q->answerText()];
            }
        }
        $level = $test->levelFor($score, $n);
        $cut = $test->cutoffs($n);
        $levels = [];
        $keys = array_keys($cut);
        foreach ($keys as $i => $lv) {
            $max = isset($keys[$i + 1]) ? $cut[$keys[$i + 1]] - 1 : $n;
            $levels[] = ['level' => $lv, 'from' => $cut[$lv], 'to' => $max, 'text' => LevelTest::LEVELS[$lv][1]];
        }

        return response()->json([
            'score' => $score, 'total' => $n, 'level' => $level,
            'description' => LevelTest::LEVELS[$level][1], 'levels' => $levels, 'review' => $review,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\IeltsTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * IELTS practice pages on the website (works like CELPIP):
 *   /ielts/academic                         → the 5 sections (mock test + 4 skills)
 *   /ielts/academic/mock-tests              → all full mock tests
 *   /ielts/academic/{module}                → practice tests for one skill (cards)
 *   /ielts/academic/speaking/{slug}         → take a Speaking test
 *   (the same addresses work for /ielts/general-training)
 */
class IeltsPracticeController extends Controller
{
    private const FORMATS = ['academic' => 'academic', 'general-training' => 'gt'];

    public function hub(string $format)
    {
        return view('ielts.hub', $this->common($format));
    }

    public function mockList(string $format)
    {
        return view('ielts.list', $this->common($format) + ['module' => null]);
    }

    public function moduleList(string $format, string $module)
    {
        return view('ielts.list', $this->common($format) + ['module' => $module]);
    }

    /** Take one skill of one test (Reading – GT only, Writing and Speaking are built so far). */
    public function practice(string $format, string $module, string $slug)
    {
        $test = IeltsTest::with('modules')->where('format', self::FORMATS[$format])
            ->where('slug', $slug)->visible()->firstOrFail();
        $backUrl = route('ielts.practice.module', [$format, $module]);

        if ($test->isLocked()) {
            return response()->view('ielts.message', [
                'test' => $test, 'module' => $module, 'backUrl' => $backUrl,
                'heading' => 'Premium test',
                'text' => 'This is a premium test. ' . \App\Support\PaidAccess::message('ielts'),
            ], 403);
        }
        if (! $test->moduleReady($module)) {
            return view('ielts.message', [
                'test' => $test, 'module' => $module, 'backUrl' => $backUrl,
                'heading' => 'Coming soon',
                'text' => 'The ' . strtolower(IeltsTest::PUBLIC_MODULES[$module]['label']) . ' section of this test is still being prepared. Please check back later, or try another test.',
            ]);
        }
        // Reading is marked automatically – no login needed (a logged-in student's score is saved to their portal).
        if (in_array($module, ['reading', 'listening'], true)) {
            return view('ielts.' . $module, ['test' => $test, 'backUrl' => $backUrl]);
        }

        // Writing and Speaking are evaluated by the teacher, so the student must be logged in.
        if (! Auth::guard('student')->check()) {
            return redirect()->route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']);
        }

        return view('ielts.' . $module, ['test' => $test, 'backUrl' => $backUrl]);
    }

    /** Reading: mark the answers on the server (the correct answers are never sent to the browser). */
    public function scoreReading(\Illuminate\Http\Request $request, IeltsTest $test)
    {
        return $this->score($request, $test, 'reading');
    }

    /** Listening is marked the same way. */
    public function scoreListening(\Illuminate\Http\Request $request, IeltsTest $test)
    {
        return $this->score($request, $test, 'listening');
    }

    private function score(\Illuminate\Http\Request $request, IeltsTest $test, string $module)
    {
        abort_unless($test->moduleReady($module) && ! $test->isLocked() && ($test->status === 'active' || \Illuminate\Support\Facades\Auth::check()), 404);
        $class = $module === 'listening' ? \App\Support\IeltsListening::class : \App\Support\IeltsReading::class;
        $data = $request->validate(['answers' => ['nullable', 'string', 'max:100000']]);
        $answers = json_decode($data['answers'] ?? '{}', true);
        $result = $class::mark(
            $class::normalize($test->moduleContent($module)),
            is_array($answers) ? $answers : [],
            $test->format === 'academic'
        );

        if ($student = Auth::guard('student')->user()) {
            \App\Models\StudentResult::create([
                'student_id' => $student->id, 'exam' => 'ielts', 'test_id' => $test->id,
                'test_name'  => $test->name . ' (' . $test->formatShort() . ')', 'module' => $module, 'mode' => 'practice',
                'score'      => $result['score'], 'total' => $result['total'], 'level' => 'Band ' . $result['band'],
            ]);
            $result['saved'] = true;
        }

        $result['vocab'] = \App\Http\Controllers\VocabularyController::summary($test, $module);

        return response()->json($result);
    }

    /** Old address /ielts/{format}/{slug}/speaking → the new one. */
    public function oldSpeaking(string $format, string $slug)
    {
        return redirect()->route('ielts.practice.test', [$format, 'speaking', $slug], 301);
    }

    private function common(string $format): array
    {
        return [
            'format' => $format,
            'label'  => IeltsTest::FORMATS[self::FORMATS[$format]],
            'tests'  => $this->tests($format),
        ];
    }

    private function tests(string $format): Collection
    {
        return IeltsTest::with('modules')->where('format', self::FORMATS[$format])->visible()->orderBy('id')->get();
    }
}

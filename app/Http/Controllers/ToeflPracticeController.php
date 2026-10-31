<?php

namespace App\Http\Controllers;

use App\Models\StudentResult;
use App\Models\TestSubmission;
use App\Models\ToeflTest;
use App\Support\Toefl;
use App\Support\ToeflPlay;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * TOEFL iBT (2026 format) on the website:
 *   /toefl                      → introduction
 *   /toefl/practice-tests       → mock test + the four sections
 *   /toefl/mock-tests           → all full mock tests      (/toefl/mock-tests/{test} takes one)
 *   /toefl/{section}            → practice tests for one section
 *   /toefl/{section}/{slug}     → take one section
 */
class ToeflPracticeController extends Controller
{
    public function landing()
    {
        return view('toefl.landing');
    }

    public function hub()
    {
        return view('toefl.hub', ['tests' => $this->tests()]);
    }

    public function mockList()
    {
        return view('toefl.list', ['module' => null, 'tests' => $this->tests()]);
    }

    public function moduleList(string $module)
    {
        return view('toefl.list', ['module' => $module, 'tests' => $this->tests()]);
    }

    public function mock(string $slug)
    {
        $test = ToeflTest::with('modules')->where('slug', $slug)->visible()->firstOrFail();
        $back = route('toefl.mocks');
        if ($test->isLocked()) return response()->view('toefl.message', ['test' => $test, 'backUrl' => $back, 'heading' => 'Premium test', 'text' => 'This is a premium test. ' . \App\Support\PaidAccess::message('toefl')], 403);
        $modules = array_values(array_filter(array_keys(Toefl::SECTIONS), fn ($m) => $test->moduleFilled($m)));
        if (! $modules) return response()->view('toefl.message', ['test' => $test, 'backUrl' => $back, 'heading' => 'Coming soon', 'text' => 'This test is still being prepared. Please try another test.']);
        if (! Auth::guard('student')->check() && array_intersect($modules, ['writing', 'speaking'])) {
            return redirect()->route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']);
        }

        return $this->exam($test, 'mock', $modules, $back);
    }

    public function practice(string $module, string $slug)
    {
        $row = \App\Models\ToeflTestModule::where('module', $module)->where('slug', $slug)->firstOrFail();
        $test = ToeflTest::with('modules')->where('id', $row->toefl_test_id)->visible()->firstOrFail();
        $back = route('toefl.module', $module);
        if ($test->isLocked()) return response()->view('toefl.message', ['test' => $test, 'backUrl' => $back, 'heading' => 'Premium test', 'text' => 'This is a premium test. ' . \App\Support\PaidAccess::message('toefl')], 403);
        if (! $test->moduleFilled($module)) return response()->view('toefl.message', ['test' => $test, 'backUrl' => $back, 'heading' => 'Coming soon', 'text' => 'This section is still being prepared. Please check back later, or try another test.']);
        if (in_array($module, ['writing', 'speaking'], true) && ! Auth::guard('student')->check()) {
            return redirect()->route('student.login', ['return' => request()->fullUrl(), 'reason' => 'test']);
        }

        return $this->exam($test, 'practice', [$module], $back);
    }

    private function exam(ToeflTest $test, string $mode, array $modules, string $back)
    {
        $sections = [];
        foreach ($modules as $m) $sections[] = ToeflPlay::payload($m, $test->moduleContent($m), $test->id);

        return view('toefl.exam', [
            'test' => $test, 'mode' => $mode, 'modules' => $modules, 'backUrl' => $back,
            'config' => ['testId' => $test->id, 'mode' => $mode, 'name' => $test->name, 'sections' => $sections,
                'student' => Auth::guard('student')->check() ? ['id' => Auth::guard('student')->id(), 'name' => Auth::guard('student')->user()->name] : null],
        ]);
    }

    /** Reading, Listening and the Build a Sentence tasks are marked here; correct answers never reach the browser before this. */
    public function score(Request $request)
    {
        $data = $request->validate([
            'test_id' => ['required', 'integer', 'exists:toefl_tests,id'],
            'section' => ['required', 'in:reading,listening,writing'],
            'mode'    => ['required', 'in:practice,mock'],
            'answers' => ['nullable', 'string', 'max:200000'],
        ]);
        $test = ToeflTest::with('modules')->visible()->findOrFail($data['test_id']);
        abort_if($test->isLocked(), 403);
        $answers = json_decode($data['answers'] ?? '{}', true);
        $result = ToeflPlay::mark($data['section'], $test->moduleContent($data['section']), is_array($answers) ? $answers : []);

        if (in_array($data['section'], ['reading', 'listening'], true) && ($student = Auth::guard('student')->user())) {
            StudentResult::create(['student_id' => $student->id, 'exam' => 'toefl', 'test_id' => $test->id, 'test_name' => $test->name,
                'module' => $data['section'], 'mode' => $data['mode'], 'score' => $result['score'], 'total' => $result['total'], 'level' => null]);
            $result['saved'] = true;
        }

        return response()->json($result);
    }

    /** Writing answers and Speaking recordings are saved for the student; they ask for feedback afterwards. */
    public function submit(Request $request)
    {
        $student = Auth::guard('student')->user();
        abort_unless($student, 401, 'Please log in again – your session has ended.');
        $data = $request->validate([
            'test_id'      => ['required', 'integer', 'exists:toefl_tests,id'],
            'module'       => ['required', 'in:writing,speaking'],
            'mode'         => ['required', 'in:practice,mock'],
            'answers'      => ['nullable', 'string', 'max:200000'],
            'recordings'   => ['nullable', 'array', 'max:20'],
            'recordings.*' => ['file', 'max:30720', 'mimetypes:audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/aac,audio/x-m4a'],
        ], ['recordings.*.max' => 'A recording was too large to upload.']);

        $test = ToeflTest::with('modules')->findOrFail($data['test_id']);
        abort_if($test->isLocked(), 403, 'This is a premium test.');
        $sent = json_decode($data['answers'] ?? '{}', true) ?: [];
        $content = $test->moduleContent($data['module']);
        $submission = TestSubmission::create([
            'student_id' => $student->id, 'exam' => 'toefl', 'test_id' => $test->id, 'test_name' => $test->name, 'mode' => $data['mode'],
            'module' => $data['module'], 'student_name' => $student->name, 'student_email' => $student->email, 'status' => 'saved', 'answers' => [],
        ]);

        $out = [];
        foreach ($content['items'] ?? [] as $it) {
            $key = $it['id'];
            $label = Toefl::TYPES[$data['module']][$it['type']] ?? $it['type'];
            if ($data['module'] === 'writing' && in_array($it['type'], ['email', 'discussion'], true)) {
                $answer = mb_substr((string) ($sent[$key] ?? ''), 0, 20000);
                $q = $it['type'] === 'email'
                    ? '<p><strong>To:</strong> ' . e($it['to'] ?? '') . ' · <strong>Subject:</strong> ' . e($it['subject'] ?? '') . '</p>' . ($it['statement'] ?? '') . ($it['instructions'] ?? '')
                    : ($it['professor_post'] ?? '') . ($it['student1_post'] ?? '') . ($it['student2_post'] ?? '') . ($it['instructions'] ?? '');
                $out[$key] = ['title' => $label, 'question' => $q, 'answer' => $answer, 'words' => preg_match_all('/\S+/u', $answer)];
            } elseif ($data['module'] === 'speaking') {
                $file = $request->file('recordings.' . $key);
                $name = null;
                if ($file) {
                    File::ensureDirectoryExists($submission->folder());
                    $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm');
                    $name = 'r-' . (count($out) + 1) . '.' . (in_array($ext, ['webm', 'ogg', 'oga', 'mp4', 'm4a', 'mp3', 'wav', 'aac'], true) ? $ext : 'webm');
                    $file->move($submission->folder(), $name);
                }
                $out[$key] = ['title' => $label . ' ' . (count($out) + 1), 'question' => e($it['statement'] ?? '') ?: '<p><em>(audio question)</em></p>',
                    'question_audio' => $it['audio'] ?? null, 'image' => $it['image'] ?? '', 'file' => $name];
            }
        }
        $submission->answers = $out;
        $submission->save();

        return response()->json(['ok' => true, 'id' => $submission->id, 'request' => route('toefl.submissions.request', $submission)]);
    }

    /** "Request feedback": the teacher sees it in Evaluation Requests → TOEFL. */
    public function requestFeedback(TestSubmission $submission)
    {
        $student = Auth::guard('student')->user();
        abort_unless($student && $submission->student_id === $student->id && $submission->exam === 'toefl', 403);
        if ($submission->status === 'saved') $submission->update(['status' => 'pending']);

        return response()->json(['ok' => true]);
    }

    private function tests(): Collection
    {
        return ToeflTest::with('modules')->visible()->orderBy('id')->get();
    }
}

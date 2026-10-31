<?php

namespace App\Http\Controllers;

use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\TestSubmission;
use App\Support\CelpipSpeaking;
use App\Support\IeltsSpeaking;
use App\Support\IeltsWriting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * Receives a student's Writing answers or Speaking recordings from the test page
 * and stores them for the teacher (Admin → Evaluation Requests → CELPIP / IELTS Evaluation).
 */
class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        // Only logged-in students can send Writing / Speaking answers.
        $student = Auth::guard('student')->user();
        abort_unless($student, 401, 'Please log in again – your session has ended.');

        $data = $request->validate([
            'test_id'       => ['required', 'integer', 'exists:celpip_tests,id'],
            'module'        => ['required', 'in:writing,speaking'],
            'mode'          => ['required', 'in:practice,mock'],
            'attempt'       => ['nullable', 'string', 'max:40'],
            'answers'       => ['nullable', 'string', 'max:200000'],
            'recordings'    => ['nullable', 'array', 'max:8'],
            'recordings.*'  => ['file', 'max:30720', 'mimetypes:audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/aac,audio/x-m4a'],
        ], [
            'recordings.*.max'       => 'A recording was too large to upload.',
        ]);

        $test = CelpipTest::with('modules')->findOrFail($data['test_id']);
        abort_if($test->isLocked(), 403, 'This is a premium test.');
        $sent = json_decode($data['answers'] ?? '[]', true) ?: [];

        $submission = TestSubmission::create([
            'student_id'    => $student->id,
            'exam'          => 'celpip',
            'test_id'       => $test->id,
            'test_name'     => $test->name,
            'mode'          => $data['mode'],
            'module'        => $data['module'],
            'attempt'       => $data['attempt'] ?? null,
            'student_name'  => $student->name,
            'student_email' => $student->email,
            'answers'       => [],
        ]);

        $submission->answers = $data['module'] === 'writing'
            ? $this->writingAnswers($test, $sent)
            : $this->speakingAnswers($test, $request, $submission, $sent);
        $submission->save();

        return response()->json(['ok' => true, 'id' => $submission->id]);
    }

    /**
     * IELTS Speaking: recordings named p1-q1, p1-q2 … p2-q1 … p3-q1 …
     * Each is stored with the question as it was when the student took the test.
     */
    public function storeIelts(Request $request)
    {
        $student = Auth::guard('student')->user();
        abort_unless($student, 401, 'Please log in again – your session has ended.');

        $data = $request->validate([
            'test_id'      => ['required', 'integer', 'exists:ielts_tests,id'],
            'module'       => ['nullable', 'in:speaking,writing'],
            'answers'      => ['nullable', 'string', 'max:200000'],
            'recordings'   => ['nullable', 'array', 'max:60'],
            'recordings.*' => ['file', 'max:30720', 'mimetypes:audio/webm,video/webm,audio/ogg,video/ogg,audio/mp4,video/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/aac,audio/x-m4a'],
        ], [
            'recordings.*.max' => 'A recording was too large to upload.',
        ]);

        $test = IeltsTest::with('modules')->findOrFail($data['test_id']);
        abort_if($test->isLocked(), 403, 'This is a premium test.');
        if (($data['module'] ?? 'speaking') === 'writing') {
            return $this->ieltsWriting($test, $student, json_decode($data['answers'] ?? '[]', true) ?: []);
        }
        $content = IeltsSpeaking::forEditing($test->moduleContent('speaking'));

        $submission = TestSubmission::create([
            'student_id'    => $student->id,
            'exam'          => 'ielts',
            'test_id'       => $test->id,
            'test_name'     => $test->name . ' (' . $test->formatShort() . ')',
            'mode'          => 'practice',
            'module'        => 'speaking',
            'student_name'  => $student->name,
            'student_email' => $student->email,
            'answers'       => [],
        ]);

        $out = [];
        foreach (IeltsSpeaking::PARTS as $p => $info) {
            foreach (IeltsSpeaking::questions($content, $p) as $i => $question) {
                $key = 'p' . $p . '-q' . ($i + 1);
                $file = $request->file('recordings.' . $key);
                $name = null;
                if ($file) {
                    File::ensureDirectoryExists($submission->folder());
                    $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm');
                    $ext = in_array($ext, ['webm', 'ogg', 'oga', 'mp4', 'm4a', 'mp3', 'wav', 'aac'], true) ? $ext : 'webm';
                    $name = $key . '.' . $ext;
                    $file->move($submission->folder(), $name);
                }
                $out[$key] = [
                    'title'    => 'Part ' . $p . ($info['many'] ? ' · Question ' . ($i + 1) : ': ' . $info['name']),
                    // Parts 1 and 3: the question's text (for the teacher) + its audio, as the student heard it
                    'question' => $info['many'] ? ($question['text'] ?: '<p><em>(audio question – no text written)</em></p>') : $question,
                    'question_audio' => $info['many'] ? $question['audio'] : null,
                    'file'     => $name,
                ];
            }
        }
        $submission->answers = $out;
        $submission->save();

        return response()->json(['ok' => true, 'id' => $submission->id]);
    }

    /** IELTS Writing: each task's question (as the student saw it) + the answer and its word count. */
    private function ieltsWriting(IeltsTest $test, $student, array $sent)
    {
        $q = IeltsWriting::forEditing($test->moduleContent('writing'), $test->format);
        $out = [];
        foreach (IeltsWriting::TASKS as $key => $task) {
            $answer = mb_substr((string) ($sent[$key]['answer'] ?? ''), 0, 20000);
            $out[$key] = [
                'title'    => $task['title'] . ': ' . IeltsWriting::subtitle($key, $test->format),
                'question' => $q[$key],
                'answer'   => $answer,
                'words'    => IeltsWriting::words($answer),
                'min'      => $task['min'],
                'max'      => $task['max'],
            ];
        }
        $submission = TestSubmission::create([
            'student_id'    => $student->id,
            'exam'          => 'ielts',
            'test_id'       => $test->id,
            'test_name'     => $test->name . ' (' . $test->formatShort() . ')',
            'mode'          => 'practice',
            'module'        => 'writing',
            'student_name'  => $student->name,
            'student_email' => $student->email,
            'answers'       => $out,
        ]);

        return response()->json(['ok' => true, 'id' => $submission->id]);
    }

    /** Writing: each task's question (as it was when the student took the test) + the answer. */
    private function writingAnswers(CelpipTest $test, array $sent): array
    {
        $q = $test->moduleContent('writing');
        $out = [];
        foreach (CelpipTest::WRITING_TASKS as $key => $task) {
            $answer = (string) ($sent[$key]['answer'] ?? '');
            $choice = $sent[$key]['choice'] ?? null;
            $out[$key] = [
                'title'    => $task['title'] . ': ' . $task['subtitle'],
                'question' => (string) ($q[$key] ?? ''),
                'note'     => (string) ($q[$key . '_note'] ?? ''),
                'option'   => $choice ? (string) ($q['task2_option' . (int) $choice] ?? '') : '',
                'choice'   => $choice ? ((int) $choice === 1 ? 'A' : 'B') : null,
                'answer'   => mb_substr($answer, 0, 20000),
                'words'    => preg_match_all('/\S+/u', $answer),
            ];
        }

        return $out;
    }

    /** Speaking: each task's question + the stored recording (kept privately, played from the admin page). */
    private function speakingAnswers(CelpipTest $test, Request $request, TestSubmission $submission, array $sent = []): array
    {
        $content = CelpipSpeaking::forEditing($test->moduleContent('speaking'));
        $out = [];
        foreach (CelpipSpeaking::TASKS as $t => $info) {
            $task = $content['tasks'][$t];
            $file = $request->file('recordings.' . $t);
            if (! $file && ! CelpipSpeaking::taskReady($t, $task)) continue;
            $name = null;
            if ($file) {
                File::ensureDirectoryExists($submission->folder());
                $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm');
                $ext = in_array($ext, ['webm', 'ogg', 'oga', 'mp4', 'm4a', 'mp3', 'wav', 'aac'], true) ? $ext : 'webm';
                $name = 'task-' . $t . '.' . $ext;
                $file->move($submission->folder(), $name);
            }
            $image = $task['image'] ?? '';
            if ($t === 4 && $image === '') $image = $content['tasks'][3]['image'];
            $entry = [
                'title'    => 'Speaking Task ' . $t . ': ' . $info['name'],
                'question' => $task['text'] ?? '',
                'image'    => $image,
                'file'     => $name,
            ];
            if ($t === 5) {
                // Which of the two options the student chose (1 or 2)
                $choice = (int) ($sent[$t]['choice'] ?? 0);
                if (in_array($choice, [1, 2], true)) {
                    $entry['choice']       = $choice === 1 ? 'Option 1' : 'Option 2';
                    $entry['choice_image'] = $task['image' . $choice];
                    $entry['choice_text']  = $task['caption' . $choice];
                    $entry['auto_chosen']  = ! empty($sent[$t]['auto']);
                }
            }
            $out[$t] = $entry;
        }

        return $out;
    }
}

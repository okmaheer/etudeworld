<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TestSubmission;
use Illuminate\Http\Request;

/**
 * CELPIP Evaluation / IELTS Evaluation: students' Writing answers and Speaking recordings,
 * which the teacher reads/listens to and scores. (Listening and Reading are marked automatically.)
 */
class EvaluationController extends Controller
{
    public function index(Request $request, string $exam)
    {
        abort_unless(isset(TestSubmission::EXAMS[$exam]), 404);

        $submissions = TestSubmission::where('exam', $exam)->where('status', '!=', 'saved')
            ->when(in_array($request->status, ['pending', 'evaluated'], true), fn ($q) => $q->where('status', $request->status))
            ->when(isset(TestSubmission::MODULES[$request->module]), fn ($q) => $q->where('module', $request->module))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('student_name', 'like', '%' . $request->q . '%')
                ->orWhere('student_email', 'like', '%' . $request->q . '%')
                ->orWhere('test_name', 'like', '%' . $request->q . '%')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.evaluations.index', [
            'exam'        => $exam,
            'label'       => TestSubmission::EXAMS[$exam],
            'submissions' => $submissions,
            'pending'     => TestSubmission::where('exam', $exam)->where('status', 'pending')->count(),
        ]);
    }

    public function show(string $exam, TestSubmission $submission)
    {
        abort_unless($submission->exam === $exam, 404);

        return view('admin.evaluations.show', [
            'exam'       => $exam,
            'label'      => TestSubmission::EXAMS[$exam],
            'submission' => $submission,
        ]);
    }

    public function update(Request $request, string $exam, TestSubmission $submission)
    {
        abort_unless($submission->exam === $exam, 404);
        $data = $request->validate([
            'score'    => ['nullable', 'string', 'max:50'],
            'feedback' => ['nullable', 'string', 'max:20000'],
            'status'   => ['required', 'in:pending,evaluated'],
        ]);
        $data['evaluated_at'] = $data['status'] === 'evaluated' ? ($submission->evaluated_at ?? now()) : null;
        $submission->update($data);

        return redirect()->route('admin.evaluations.show', [$exam, $submission])
            ->with('success', $data['status'] === 'evaluated' ? 'Evaluation saved and marked as evaluated.' : 'Saved. Still marked as waiting.');
    }

    public function destroy(string $exam, TestSubmission $submission)
    {
        abort_unless($submission->exam === $exam, 404);
        $submission->delete();

        return redirect()->route('admin.evaluations.index', $exam)->with('success', 'The submission was deleted.');
    }

    /** Play a speaking recording (only for logged-in admins). */
    public function audio(string $exam, TestSubmission $submission, string $file)
    {
        abort_unless($submission->exam === $exam && preg_match('/^(task-\d+|p\d-q\d+|r-\d+)\.[a-z0-9]+$/', $file), 404);
        $path = $submission->folder() . DIRECTORY_SEPARATOR . $file;
        abort_unless(is_file($path), 404);

        return response()->file($path);
    }
}

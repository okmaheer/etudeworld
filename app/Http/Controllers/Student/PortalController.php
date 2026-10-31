<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CelpipTest;
use App\Models\StudentResult;
use App\Support\Exams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The student's personal portal: all their test results, grouped by exam. */
class PortalController extends Controller
{
    public function index(Request $request)
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return redirect()->route('student.login', ['return' => route('student.portal')]);
        }

        $vocab = $student->vocabProgress()->latest('updated_at')->get()->map(function ($p) {
            $list = $p->list_key ? \App\Support\VocabLists::fromKey($p->list_key) : null;
            if (! $list) return null;
            $p->list = $list;
            $p->total = $list['words']->count();
            $p->done = min(count($p->known ?? []), $p->total);

            return $p;
        })->filter();

        $results = $student->results()->latest()->get()->groupBy('exam');
        $submissions = $student->submissions()->latest()->get()->groupBy('exam');
        $exam = $request->query('exam') === 'vocab' || array_key_exists($request->query('exam'), Exams::ALL) ? $request->query('exam')
            : (collect(Exams::ALL)->keys()->first(fn ($e) => isset($results[$e]) || isset($submissions[$e])) ?? 'celpip');

        return view('student.portal', compact('student', 'vocab', 'results', 'submissions', 'exam'));
    }

    /** Listening / Reading scores are sent here when a signed-in student finishes them. */
    public function storeResult(Request $request)
    {
        $student = Auth::guard('student')->user();
        if (! $student) {
            return response()->json(['saved' => false]);   // not signed in: nothing to save (that's fine)
        }
        $data = $request->validate([
            'exam'    => ['required', 'in:' . implode(',', array_keys(Exams::ALL))],
            'test_id' => ['required', 'integer'],
            'module'  => ['required', 'in:listening,reading'],
            'mode'    => ['required', 'in:practice,mock'],
            'score'   => ['required', 'integer', 'min:0', 'max:500'],
            'total'   => ['required', 'integer', 'min:0', 'max:500'],
            'level'   => ['nullable', 'string', 'max:120'],
        ]);
        $test = $data['exam'] === 'celpip' ? CelpipTest::find($data['test_id']) : null;
        abort_if($data['exam'] === 'celpip' && ! $test, 422);

        StudentResult::create($data + ['student_id' => $student->id, 'test_name' => $test?->name ?? 'Test']);

        return response()->json(['saved' => true]);
    }
}

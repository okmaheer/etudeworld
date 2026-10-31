<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LevelTest;
use App\Models\LevelTestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin: English Level Tests (test details, then Section 1, 2 and 3 with their questions) */
class LevelTestController extends Controller
{
    public function index()
    {
        return view('admin.level.index', ['tests' => LevelTest::latest()->get()]);
    }

    public function create()
    {
        return view('admin.level.form', ['test' => new LevelTest(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $test = LevelTest::create($this->validated($request));

        return redirect()->route('admin.level.edit', $test)->with('success', 'Test created. Now add the questions to each section below.');
    }

    public function edit(LevelTest $test)
    {
        return view('admin.level.form', compact('test'));
    }

    public function update(Request $request, LevelTest $test)
    {
        $test->update($this->validated($request, $test));

        return redirect()->route('admin.level.edit', $test)->with('success', 'Test details saved.');
    }

    public function destroy(LevelTest $test)
    {
        $test->delete();

        return redirect()->route('admin.level.index')->with('success', 'Test "' . $test->title . '" was deleted.');
    }

    public function section(Request $request, LevelTest $test, int $n)
    {
        abort_unless(in_array($n, [1, 2, 3]), 404);
        $section = $test->section($n);
        $editing = $request->filled('q') ? $section->questions()->find($request->q) : null;

        return view('admin.level.section', [
            'test' => $test, 'section' => $section, 'n' => $n,
            'questions' => $section->questions()->get(),
            'q' => $editing ?? new LevelTestQuestion(['type' => 'mcq']),
        ]);
    }

    public function saveSection(Request $request, LevelTest $test, int $n)
    {
        abort_unless(in_array($n, [1, 2, 3]), 404);
        $data = $request->validate([
            'instructions' => 'nullable|string|max:5000',
            'passages' => 'nullable|array',
            'passages.*.title' => 'nullable|string|max:255',
            'passages.*.body' => 'nullable|string|max:20000',
        ]);
        $passages = null;
        if ($n === 3) {
            $passages = [];
            foreach ([1, 2] as $i) {
                $passages[$i] = [
                    'title' => trim($data['passages'][$i]['title'] ?? ''),
                    'body' => trim($data['passages'][$i]['body'] ?? ''),
                ];
            }
        }
        $test->section($n)->update(['instructions' => $data['instructions'] ?? null, 'passages' => $passages]);

        return back()->with('success', 'Section ' . $n . ' saved.');
    }

    public function saveQuestion(Request $request, LevelTest $test, int $n)
    {
        abort_unless(in_array($n, [1, 2, 3]), 404);
        $section = $test->section($n);
        $data = $request->validate([
            'id' => 'nullable|integer',
            'type' => ['required', Rule::in(['mcq', 'fitb'])],
            'passage' => 'nullable|in:1,2',
            'prompt' => 'required|string|max:2000',
            'options' => 'nullable|array',
            'options.*' => 'nullable|string|max:500',
            'correct' => 'nullable|integer|between:0,3',
            'answer' => 'nullable|string|max:500',
            'level' => 'nullable|in:A1,A2,B1,B2,C1,C2',
            'position' => 'nullable|integer|min:0|max:999',
        ]);

        if ($data['type'] === 'mcq') {
            $opts = array_map(fn ($o) => trim((string) $o), array_slice(array_values($data['options'] ?? []), 0, 4));
            if (count(array_filter($opts, fn ($o) => $o !== '')) < 4 || !isset($data['correct'])) {
                return back()->withInput()->withErrors(['options' => 'Fill in all four options and choose the correct one.']);
            }
            $answer = (string) $data['correct'];
        } else {
            $opts = null;
            $answer = trim(implode('|', array_filter(array_map('trim', explode('|', (string) ($data['answer'] ?? ''))))));
            if ($answer === '') {
                return back()->withInput()->withErrors(['answer' => 'Write the correct answer (use | between accepted answers).']);
            }
        }

        $fields = [
            'passage' => $n === 3 ? ($data['passage'] ?? null) : null,
            'type' => $data['type'], 'prompt' => trim($data['prompt']),
            'options' => $opts, 'answer' => $answer, 'level' => $data['level'] ?? null,
        ];
        if (!empty($data['id']) && ($q = $section->questions()->find($data['id']))) {
            if (isset($data['position'])) {
                $fields['position'] = $data['position'];
            }
            $q->update($fields);
            $msg = 'Question updated.';
        } else {
            $fields['position'] = $data['position'] ?? (($section->questions()->max('position') ?? 0) + 1);
            $section->questions()->create($fields);
            $msg = 'Question added.';
        }

        return redirect()->route('admin.level.section', [$test, $n])->with('success', $msg);
    }

    public function destroyQuestion(LevelTest $test, LevelTestQuestion $question)
    {
        $n = $question->section->number;
        $question->delete();

        return redirect()->route('admin.level.section', [$test, $n])->with('success', 'Question deleted.');
    }

    private function validated(Request $request, ?LevelTest $test = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('level_tests', 'slug')->ignore($test?->id)],
            'instructions' => 'nullable|string|max:5000',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'status' => ['required', Rule::in(['active', 'draft'])],
            'thresholds' => 'nullable|array',
            'thresholds.*' => 'nullable|integer|min:0|max:500',
        ]);
        $slug = Str::slug($data['slug'] ?: $data['title']);
        $base = $slug;
        $i = 2;
        while (LevelTest::where('slug', $slug)->where('id', '!=', $test?->id ?? 0)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $data['slug'] = $slug;
        $th = array_filter($data['thresholds'] ?? [], fn ($v) => $v !== null && $v !== '');
        $data['thresholds'] = $th ?: null;

        return $data;
    }
}

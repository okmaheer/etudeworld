<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IeltsTest;
use App\Models\IeltsTestModule;
use App\Support\IeltsSpeaking;
use App\Support\IeltsWriting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Manage IELTS: All IELTS Tests / Add an IELTS Test (works like Manage CELPIP). */
class IeltsTestController extends Controller
{
    public function index(Request $request)
    {
        $tests = IeltsTest::with('modules')
            ->when($request->filled('format'), fn ($q) => $q->where('format', $request->format))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        return view('admin.ielts.index', compact('tests'));
    }

    public function create()
    {
        return view('admin.ielts.form', ['test' => new IeltsTest(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $test = IeltsTest::create($this->validated($request));

        return redirect()->route('admin.ielts.index')->with('success', 'Test "' . $test->name . '" was added.');
    }

    public function edit(IeltsTest $test)
    {
        return view('admin.ielts.form', compact('test'));
    }

    public function update(Request $request, IeltsTest $test)
    {
        $test->update($this->validated($request, $test));

        return redirect()->route('admin.ielts.index')->with('success', 'Test "' . $test->name . '" was updated.');
    }

    public function destroy(IeltsTest $test)
    {
        $test->delete();

        return redirect()->route('admin.ielts.index')->with('success', 'Test "' . $test->name . '" was deleted.');
    }

    /** Writing / Speaking questions pages (Listening and Reading come later). */
    public function module(IeltsTest $test, string $module)
    {
        abort_unless($test->hasModule($module), 404);
        if ($module === 'writing') {
            return view('admin.ielts.writing', [
                'test'    => $test,
                'module'  => $module,
                'content' => IeltsWriting::forEditing($test->moduleContent('writing'), $test->format),
            ]);
        }

        return view('admin.ielts.speaking', [
            'test'    => $test,
            'module'  => $module,
            'content' => IeltsSpeaking::forEditing($test->moduleContent('speaking')),
        ]);
    }

    public function saveModule(Request $request, IeltsTest $test, string $module)
    {
        abort_unless(in_array($module, IeltsTest::BUILT, true), 404);
        if ($module === 'writing') {
            $content = IeltsWriting::fromRequest($request->validate([
                'task1' => ['nullable', 'string'],
                'task2' => ['nullable', 'string'],
            ]));
            IeltsTestModule::updateOrCreate(['ielts_test_id' => $test->id, 'module' => 'writing'], ['content' => $content]);

            return redirect()->route('admin.ielts.module', [$test, 'writing'])
                ->with('success', 'Writing for "' . $test->name . '" was saved.'
                    . (IeltsWriting::isReady($content) ? '' : ' Students can take it once both Task 1 and Task 2 have a question.'));
        }
        $request->validate(['speaking' => ['required', 'array']]);
        $content = IeltsSpeaking::fromRequest($request->input('speaking', []));
        IeltsTestModule::updateOrCreate(
            ['ielts_test_id' => $test->id, 'module' => 'speaking'],
            ['content' => $content],
        );
        $counts = collect(array_keys(IeltsSpeaking::PARTS))
            ->map(fn ($p) => 'Part ' . $p . ': ' . count(IeltsSpeaking::questions($content, $p)))
            ->implode(', ');

        $missing = IeltsSpeaking::missingAudio($content, 1) + IeltsSpeaking::missingAudio($content, 3);

        return redirect()->route('admin.ielts.module', [$test, 'speaking'])
            ->with('success', "Speaking for \"{$test->name}\" was saved. Questions ready – {$counts}."
                . ($missing ? " {$missing} " . ($missing === 1 ? 'question still needs' : 'questions still need') . ' an audio file (students only hear questions that have audio).' : '')
                . (IeltsSpeaking::isReady($content) ? '' : ' Students can take the test once every part has at least one ready question.'));
    }

    /** Upload a question audio or the "Start speaking now" audio (called as soon as a file is chosen). */
    public function uploadMedia(Request $request, IeltsTest $test)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:mp3,mpga,wav,m4a,mp4,aac,ogg,oga,webm', 'max:51200']], [
            'file.mimes' => 'Please choose an MP3, WAV, M4A, AAC or OGG audio file.',
            'file.max'   => 'Audio files must be smaller than 50 MB.',
        ]);
        $file = $request->file('file');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'audio';
        $name = Str::limit($base, 40, '') . '-' . Str::random(6) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $dir  = 'uploads/ielts/' . $test->id . '/speaking';
        $file->move(public_path($dir), $name);

        return response()->json(['path' => $dir . '/' . $name, 'url' => asset($dir . '/' . $name)]);
    }

    private function validated(Request $request, ?IeltsTest $test = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name'))]);

        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', Rule::unique('ielts_tests', 'slug')->ignore($test?->id)],
            'format'           => ['required', Rule::in(array_keys(IeltsTest::FORMATS))],
            'type'             => ['required', Rule::in(array_keys(IeltsTest::TYPES))],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status'           => ['required', Rule::in(array_keys(IeltsTest::STATUSES))],
        ], [
            'slug.unique' => 'Another IELTS test already uses this URL. Please choose a different one.',
        ]);
    }
}

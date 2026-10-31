<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ToeflTest;
use App\Models\ToeflTestModule;
use App\Support\Toefl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin: TOEFL iBT tests and the questions of their four sections. */
class ToeflTestController extends Controller
{
    public function index(Request $request)
    {
        $tests = ToeflTest::with('modules')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->get();

        return view('admin.toefl.index', compact('tests'));
    }

    public function create()
    {
        return view('admin.toefl.form', ['test' => new ToeflTest(['status' => 'draft', 'type' => 'free'])]);
    }

    public function store(Request $request)
    {
        $test = ToeflTest::create($this->validated($request));

        return redirect()->route('admin.toefl.index')->with('success', 'Test "' . $test->name . '" was added. Now add its questions with the section icons.');
    }

    public function edit(ToeflTest $test)
    {
        return view('admin.toefl.form', compact('test'));
    }

    public function update(Request $request, ToeflTest $test)
    {
        $test->update($this->validated($request, $test));

        return redirect()->route('admin.toefl.index')->with('success', 'Test "' . $test->name . '" was updated.');
    }

    public function destroy(ToeflTest $test)
    {
        $test->delete();

        return redirect()->route('admin.toefl.index')->with('success', 'Test "' . $test->name . '" was deleted.');
    }

    /** The questions page of one section. */
    public function module(ToeflTest $test, string $module)
    {
        abort_unless(isset(Toefl::SECTIONS[$module]), 404);
        $row = $test->moduleRow($module);

        return view('admin.toefl.section', [
            'test' => $test, 'module' => $module, 'info' => Toefl::SECTIONS[$module], 'row' => $row,
            'content' => Toefl::forEditing($module, $row?->content),
            'counts' => Toefl::counts($module, $row?->content ?? []),
        ]);
    }

    /** Save the address, meta tags and questions of one section. The page sends the questions as one JSON value. */
    public function saveModule(Request $request, ToeflTest $test, string $module)
    {
        abort_unless(isset(Toefl::SECTIONS[$module]), 404);
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $test->slug)]);
        $d = $request->validate([
            'slug' => ['required', 'string', 'max:255', Rule::unique('toefl_test_modules', 'slug')->where('module', $module)->ignore($test->moduleRow($module)?->id)],
            'content_json' => ['required', 'string', 'max:8000000'],
        ], ['slug.unique' => 'Another ' . $module . ' test already uses this URL. Please choose a different one.']);
        $input = json_decode($d['content_json'], true);
        abort_unless(is_array($input), 422, 'The page sent something unexpected. Please reload and try again.');

        $content = Toefl::fromInput($module, $input);
        ToeflTestModule::updateOrCreate(
            ['toefl_test_id' => $test->id, 'module' => $module],
            ['slug' => $d['slug'], 'content' => $content],
        );

        $counts = collect(Toefl::counts($module, $content))->map(fn ($c, $m) => ($m ? "Module $m: " : '') . "{$c['questions']} questions ({$c['graded']} graded)")->implode(', ');
        $problems = Toefl::problems($module, $content);

        return redirect()->route('admin.toefl.module', [$test, $module])
            ->with('success', Toefl::SECTIONS[$module]['label'] . ' for "' . $test->name . '" was saved. ' . ($counts ?: 'No questions yet.'))
            ->with('problems', $problems);
    }

    /** Upload one audio, video or image file (called as soon as a file is chosen). Returns its saved path. */
    public function uploadMedia(Request $request, ToeflTest $test)
    {
        $kind = in_array($request->input('kind'), ['image', 'video'], true) ? $request->input('kind') : 'audio';
        $rules = [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'video' => ['required', 'file', 'mimes:mp4,webm,mov,m4v', 'max:204800'],
            'audio' => ['required', 'file', 'mimes:mp3,mpga,wav,m4a,mp4,aac,ogg,oga,webm', 'max:51200'],
        ][$kind];
        $request->validate(['file' => $rules], [
            'file.mimes' => ['image' => 'Please choose a JPG, PNG, WebP or GIF image.', 'video' => 'Please choose an MP4, WebM or MOV video.', 'audio' => 'Please choose an MP3, WAV, M4A, AAC or OGG audio file.'][$kind],
            'file.max' => ['image' => 'Images must be smaller than 8 MB.', 'video' => 'Videos must be smaller than 200 MB.', 'audio' => 'Audio files must be smaller than 50 MB.'][$kind],
        ]);

        $file = $request->file('file');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $kind;
        $name = Str::limit($base, 40, '') . '-' . Str::random(6) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $folder = isset(Toefl::SECTIONS[$request->input('folder')]) ? $request->input('folder') : 'misc';
        $dir = 'uploads/toefl/' . $test->id . '/' . $folder;
        $file->move(public_path($dir), $name);

        return response()->json(['path' => $dir . '/' . $name, 'url' => asset($dir . '/' . $name)]);
    }

    private function validated(Request $request, ?ToeflTest $test = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('toefl_tests', 'slug')->ignore($test?->id)],
            'type' => ['required', Rule::in(array_keys(ToeflTest::TYPES))],
            'status' => ['required', Rule::in(array_keys(ToeflTest::STATUSES))],
        ], ['slug.unique' => 'Another test already uses this URL. Please choose a different one.']);
    }
}

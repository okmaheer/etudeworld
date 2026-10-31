<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CelpipTest;
use App\Models\CelpipTestModule;
use App\Support\CelpipListening;
use App\Support\CelpipReading;
use App\Support\CelpipSpeaking;
use Illuminate\Support\Str as Strings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CelpipTestController extends Controller
{
    /** All CELPIP Tests */
    public function index(Request $request)
    {
        $tests = CelpipTest::with('modules')
            ->when($request->filled('format'), fn ($q) => $q->where('format', $request->format))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        return view('admin.celpip.index', compact('tests'));
    }

    /** Add a CELPIP Test */
    public function create()
    {
        return view('admin.celpip.form', ['test' => new CelpipTest(['status' => 'draft'])]);
    }

    public function store(Request $request)
    {
        $test = CelpipTest::create($this->validated($request));

        return redirect()->route('admin.celpip.index')
            ->with('success', 'Test "' . $test->name . '" was added.');
    }

    public function edit(CelpipTest $test)
    {
        return view('admin.celpip.form', compact('test'));
    }

    public function update(Request $request, CelpipTest $test)
    {
        $test->update($this->validated($request, $test));

        return redirect()->route('admin.celpip.index')
            ->with('success', 'Test "' . $test->name . '" was updated.');
    }

    public function destroy(CelpipTest $test)
    {
        $test->delete();

        return redirect()->route('admin.celpip.index')
            ->with('success', 'Test "' . $test->name . '" was deleted.');
    }

    /** Writing / Speaking / Reading / Listening question pages. */
    public function module(CelpipTest $test, string $module)
    {
        abort_unless(isset(CelpipTest::MODULES[$module]), 404);

        $data = [
            'test'    => $test,
            'module'  => $module,
            'info'    => CelpipTest::MODULES[$module],
            'content' => $test->modules()->where('module', $module)->first()?->content ?? [],
        ];

        // Writing and Listening have their own pages; the other sections are built later.
        if ($module === 'writing' && $test->hasModule('writing')) {
            return view('admin.celpip.writing', $data);
        }
        if ($module === 'listening') {
            $data['content'] = CelpipListening::forEditing($data['content']);
            return view('admin.celpip.listening', $data);
        }
        if ($module === 'reading' && $test->hasModule('reading')) {
            $data['content'] = CelpipReading::forEditing($data['content']);
            return view('admin.celpip.reading', $data);
        }
        if ($module === 'speaking') {
            $data['content'] = CelpipSpeaking::forEditing($data['content']);
            return view('admin.celpip.speaking', $data);
        }

        return view('admin.celpip.module', $data);
    }

    /** Save the questions of one section. */
    public function saveModule(Request $request, CelpipTest $test, string $module)
    {
        if ($module === 'listening') {
            return $this->saveListening($request, $test);
        }
        if ($module === 'reading' && $test->hasModule('reading')) {
            return $this->saveReading($request, $test);
        }
        if ($module === 'speaking') {
            $request->validate(['speaking' => ['required', 'array']]);
            $content = CelpipSpeaking::fromRequest($request->input('speaking', []));
            CelpipTestModule::updateOrCreate(
                ['celpip_test_id' => $test->id, 'module' => 'speaking'],
                ['content' => $content],
            );
            $written = collect($content['tasks'])->filter(fn ($task, $t) => CelpipSpeaking::taskReady((int) $t, $task))->count();

            return redirect()->route('admin.celpip.module', [$test, 'speaking'])
                ->with('success', "Speaking for \"{$test->name}\" was saved. {$written} of 8 tasks ready.");
        }

        abort_unless($module === 'writing' && $test->hasModule('writing'), 404);

        $content = $request->validate([
            'task1' => ['nullable', 'string'],
            'task2' => ['nullable', 'string'],
            // Text Editor 2 of each task: shown above the student's response box
            'task1_note' => ['nullable', 'string'],
            'task2_note' => ['nullable', 'string'],
            // Task 2: the two choices the student picks from
            'task2_option1' => ['nullable', 'string'],
            'task2_option2' => ['nullable', 'string'],
        ]);

        CelpipTestModule::updateOrCreate(
            ['celpip_test_id' => $test->id, 'module' => $module],
            ['content' => array_map(fn ($v) => $v ?? '', $content)],
        );

        return redirect()->route('admin.celpip.module', [$test, $module])
            ->with('success', 'Writing questions for "' . $test->name . '" were saved.');
    }

    /** Save the Listening section (Part 1 for now). */
    private function saveListening(Request $request, CelpipTest $test)
    {
        $request->validate(['listening' => ['required', 'array']]);
        $content = CelpipListening::fromRequest($request->input('listening', []));

        // Only keep file paths that point to this website's own files.
        array_walk_recursive($content, function (&$value, $key) {
            if (in_array($key, ['audio', 'image'], true) && $value !== ''
                && ! preg_match('#^(uploads/celpip/\d+/|audio/celpip/)[\w./-]+$#', $value)) {
                $value = '';
            }
            if ($key === 'correct' && ! in_array($value, ['0', '1', '2', '3'], true)) {
                $value = '';
            }
        });

        CelpipTestModule::updateOrCreate(
            ['celpip_test_id' => $test->id, 'module' => 'listening'],
            ['content' => $content],
        );

        $summary = collect(array_keys(CelpipListening::PART_DIVISIONS))->map(function ($part) use ($content) {
            $p = CelpipListening::partProgress($content, $part);
            return "Part {$part}: {$p['done']} of {$p['total']}";
        })->implode(', ');

        return redirect()->route('admin.celpip.module', [$test, 'listening'])
            ->with('success', "Listening for \"{$test->name}\" was saved. Questions complete – {$summary}.");
    }

    /** Save the Reading section. The page sends everything as one JSON value ("reading_json"). */
    private function saveReading(Request $request, CelpipTest $test)
    {
        $request->validate(['reading_json' => ['required', 'string', 'max:5000000']]);
        $input = json_decode($request->input('reading_json'), true);
        abort_unless(is_array($input), 422, 'The reading page sent something unexpected. Please reload and try again.');

        $content = CelpipReading::fromInput($input);
        CelpipTestModule::updateOrCreate(
            ['celpip_test_id' => $test->id, 'module' => 'reading'],
            ['content' => $content],
        );

        $summary = collect(array_keys(CelpipReading::PARTS))->map(function ($part) use ($content) {
            $p = CelpipReading::partProgress($content, $part);
            return "Part {$part}: {$p['done']} of {$p['total']}";
        })->implode(', ');

        return redirect()->route('admin.celpip.module', [$test, 'reading'])
            ->with('success', "Reading for \"{$test->name}\" was saved. Questions complete – {$summary}.");
    }

    /**
     * Upload one audio or image file for a test (called by the page as soon as a file is chosen).
     * Returns the saved path, which the form then sends with the rest of the page.
     */
    public function uploadMedia(Request $request, CelpipTest $test)
    {
        $kind = $request->input('kind') === 'image' ? 'image' : 'audio';
        $rules = $kind === 'image'
            ? ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192']
            : ['required', 'file', 'mimes:mp3,mpga,wav,m4a,mp4,aac,ogg,oga,webm', 'max:51200'];
        $request->validate(['file' => $rules], [
            'file.mimes' => $kind === 'image' ? 'Please choose a JPG, PNG, WebP or GIF image.' : 'Please choose an MP3, WAV, M4A, AAC or OGG audio file.',
            'file.max'   => $kind === 'image' ? 'Images must be smaller than 8 MB.' : 'Audio files must be smaller than 50 MB.',
        ]);

        $file = $request->file('file');
        $base = Strings::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: $kind;
        $name = Strings::limit($base, 40, '') . '-' . Strings::random(6) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $folder = in_array($request->input('folder'), ['reading', 'speaking'], true) ? $request->input('folder') : 'listening';
        $dir  = 'uploads/celpip/' . $test->id . '/' . $folder;
        $file->move(public_path($dir), $name);

        return response()->json(['path' => $dir . '/' . $name, 'url' => asset($dir . '/' . $name)]);
    }

    private function validated(Request $request, ?CelpipTest $test = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name'))]);

        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', Rule::unique('celpip_tests', 'slug')->ignore($test?->id)],
            'format'           => ['required', Rule::in(array_keys(CelpipTest::FORMATS))],
            'type'             => ['required', Rule::in(array_keys(CelpipTest::TYPES))],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status'           => ['required', Rule::in(array_keys(CelpipTest::STATUSES))],
        ], [
            'slug.unique' => 'Another test already uses this URL. Please choose a different one.',
        ]);
    }
}

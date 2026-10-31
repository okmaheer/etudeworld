<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IeltsTest;
use App\Models\IeltsTestModule;
use App\Support\IeltsListening;
use App\Support\IeltsReading as R;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * IELTS (GT) Reading in the admin panel:
 *   home page – buttons + a preview of the passages and every question group
 *   passages  – the three reading passages / sections
 *   questions – one form per question type (FITB, flow chart, table, paragraph, TRUE/FALSE, MCQ, headings, matching, MCQ 5 options)
 */
class IeltsReadingController extends Controller
{
    /** 'reading' here; IeltsListeningController changes these two lines and everything else is shared. */
    protected string $module = 'reading';

    protected string $cls = R::class;

    protected function R(): string
    {
        return $this->cls;
    }

    private function content(IeltsTest $test): array
    {
        return $this->R()::normalize($test->moduleContent($this->module));
    }

    private function store(IeltsTest $test, array $content): void
    {
        IeltsTestModule::updateOrCreate(['ielts_test_id' => $test->id, 'module' => $this->module], ['content' => $content]);
        if ($this->module === 'listening') \App\Support\SpellingCollector::sync($test);   // keep the Important Spellings list up to date
    }

    private function guard(IeltsTest $test): void
    {
        abort_unless($test->hasModule($this->module), 404, 'This test does not have this section.');
    }

    /** [id-1] the Reading home page. */
    public function home(IeltsTest $test)
    {
        $this->guard($test);
        $content = $this->content($test);

        return view('admin.ielts.reading.index', [
            'test'    => $test,
            'module'  => $this->module,
            'R'       => $this->R(),
            'content' => $content,
            'ordered' => $this->R()::ordered($content),
            'ranges'  => $this->R()::ranges($content),
            'total'   => $this->R()::total($content),
            'key'     => $this->R()::answerKey($content),
        ]);
    }

    /** [id-3] the three passages. */
    public function passages(IeltsTest $test)
    {
        $this->guard($test);

        return view('admin.ielts.reading.passages', ['R' => $this->R(), 'module' => $this->module, 'test' => $test, 'content' => $this->content($test)]);
    }

    public function savePassages(Request $request, IeltsTest $test)
    {
        $this->guard($test);
        $request->validate(['passages' => ['array'], 'passages.*' => ['nullable', 'string', 'max:500000']]);
        $content = $this->content($test);
        foreach ($this->R()::PARTS as $p) $content['passages'][$p] = (string) $request->input("passages.$p", '');
        $this->store($test, $content);

        return redirect()->route('admin.ielts.' . $this->module, $test)->with('success', ($this->module === 'listening' ? 'The transcripts were saved.' : 'The reading passages were saved.'));
    }

    /** A question form – new ($type) or existing ($group). */
    public function create(IeltsTest $test, string $type)
    {
        $this->guard($test);
        abort_unless(isset($this->R()::TYPES[$type]), 404);

        return $this->form($test, $this->R()::cleanGroup(['type' => $type, 'passage' => (int) request('passage', 1), 'instructions' => $this->defaultInstructions($type), 'items' => []]), true);
    }

    public function edit(IeltsTest $test, string $group)
    {
        $this->guard($test);
        $g = collect($this->content($test)['groups'])->firstWhere('id', $group);
        abort_unless($g, 404);

        return $this->form($test, $g, false);
    }

    private function form(IeltsTest $test, array $group, bool $isNew)
    {
        $content = $this->content($test);
        // The first question number this group would get in each passage:
        // in its own passage it keeps its place; in another passage it goes after that passage's questions.
        $starts = [];
        foreach ($this->R()::PARTS as $p) {
            $before = 0;
            foreach ($this->R()::ordered($content) as $g) {
                if ($g['id'] === $group['id']) {
                    if ($g['passage'] === $p) break;
                    continue;
                }
                if ($g['passage'] > $p) break;
                $before += $g['count'];
            }
            $starts[$p] = $before + 1;
        }
        $letters = [];
        foreach ($this->R()::PARTS as $p) $letters[$p] = $this->R()::letters($content['passages'][$p]);

        return view(match ($group['type']) { 'mapdrop' => 'admin.ielts.reading.mapdrop', 'fitb_label' => 'admin.ielts.reading.fitblabel', default => 'admin.ielts.reading.question' }, [
            'test'    => $test,
            'R'       => $this->R(),
            'module'  => $this->module,
            'group'   => $group,
            'isNew'   => $isNew,
            'type'    => $group['type'],
            'info'    => $this->R()::TYPES[$group['type']],
            'starts'  => $starts,
            'letters' => $letters,
            'hasText' => array_map(fn ($html) => \App\Support\IeltsSpeaking::filled($html), $content['passages']),
        ]);
    }

    /** Save a question group (new or edited). The form sends everything as one JSON value. */
    public function save(Request $request, IeltsTest $test)
    {
        $this->guard($test);
        $request->validate(['group_json' => ['required', 'string', 'max:2000000']]);
        $input = json_decode($request->input('group_json'), true);
        abort_unless(is_array($input) && isset($this->R()::TYPES[$input['type'] ?? '']), 422, 'The form sent something unexpected. Please reload and try again.');

        $group = $this->R()::cleanGroup($input);
        $content = $this->content($test);
        $found = false;
        foreach ($content['groups'] as $i => $g) {
            if ($g['id'] !== $group['id']) continue;
            $found = true;
            if ($g['passage'] !== $group['passage']) {
                // Moved to another passage: put it after that passage's other questions.
                array_splice($content['groups'], $i, 1);
                $content['groups'][] = $group;
            } else {
                $content['groups'][$i] = $group;
            }
            break;
        }
        if (! $found) $content['groups'][] = $group;
        $this->store($test, $content);

        $placed = collect($this->R()::ordered($content))->firstWhere('id', $group['id']);
        $range = $placed['count'] ? $this->R()::questionRange($placed['start'], $placed['count']) : 'no questions yet';

        return redirect()->route('admin.ielts.' . $this->module, $test)
            ->with('success', $this->R()::TYPES[$group['type']]['label'] . ' (' . $range . ', ' . ($this->module === 'listening' ? 'Part ' : 'Passage ') . $group['passage'] . ') was saved.');
    }

    public function destroy(IeltsTest $test, string $group)
    {
        $this->guard($test);
        $content = $this->content($test);
        $content['groups'] = array_values(array_filter($content['groups'], fn ($g) => $g['id'] !== $group));
        $this->store($test, $content);

        return redirect()->route('admin.ielts.' . $this->module, $test)->with('success', 'The question group was deleted. Question numbers after it were updated.');
    }

    /** Move a group up or down within its passage. */
    public function move(IeltsTest $test, string $group, string $dir)
    {
        $this->guard($test);
        $content = $this->content($test);
        $groups = $content['groups'];
        $i = collect($groups)->search(fn ($g) => $g['id'] === $group);
        abort_if($i === false, 404);
        $step = $dir === 'up' ? -1 : 1;
        for ($j = $i + $step; $j >= 0 && $j < count($groups); $j += $step) {
            if ($groups[$j]['passage'] === $groups[$i]['passage']) {
                [$groups[$i], $groups[$j]] = [$groups[$j], $groups[$i]];
                break;
            }
        }
        $content['groups'] = $groups;
        $this->store($test, $content);

        return redirect()->route('admin.ielts.' . $this->module, $test);
    }

    /** IELTS-style instructions filled in for new question groups (the admin can change them). */
    protected function defaultInstructions(string $type): string
    {
        if ($this->module === 'listening') {
            return match ($type) {
                'drag'       => '<p>Choose the correct answer for each question and move it into the gap.</p>',
                'mapdrop'    => '<p>Label the map below.</p><p>Choose the correct answer for each box and move it into the box on the map.</p>',
                'map'        => '<p>Label the map below.</p><p>Choose the correct letter, <strong>A–E</strong>, next to each question.</p>',
                'fitb'       => '<p>Complete the notes below.</p><p>Write <strong>ONE WORD AND/OR A NUMBER</strong> for each answer.</p>',
                'fitb_flow'  => '<p>Complete the flow chart below.</p><p>Write <strong>ONE WORD AND/OR A NUMBER</strong> for each answer.</p>',
                'fitb_table' => '<p>Complete the table below.</p><p>Write <strong>ONE WORD AND/OR A NUMBER</strong> for each answer.</p>',
                'mcq'        => '<p>Choose the correct letter, <strong>A, B</strong> or <strong>C</strong>.</p>',
                'mcq5'       => '<p>Choose <strong>TWO</strong> letters, <strong>A–E</strong>.</p>',
                default      => '',
            };
        }

        return match ($type) {
            'fitb'       => '<p>Complete the sentences below.</p><p>Choose <strong>NO MORE THAN TWO WORDS</strong> from the text for each answer.</p>',
            'fitb_flow'  => '<p>Complete the flow chart below.</p><p>Choose <strong>NO MORE THAN TWO WORDS</strong> from the text for each answer.</p>',
            'fitb_table' => '<p>Complete the table below.</p><p>Choose <strong>NO MORE THAN TWO WORDS</strong> from the text for each answer.</p>',
            'fitb_para'  => '<p>Complete the summary below.</p><p>Choose <strong>ONE WORD ONLY</strong> from the text for each answer.</p>',
            'fitb_label' => '<p>Complete the diagram below.</p><p>Write <strong>NO MORE THAN TWO WORDS</strong> for each answer.</p>',
            'fitb_syn'   => '<p>Complete the summary using the list of words, <strong>A–G</strong>, below.</p><p>Write the correct letter, <strong>A–G</strong>, in the boxes on your answer sheet.</p>',
            'tfng'       => '<p>Do the following statements agree with the information given in the text?</p><p>Choose <strong>TRUE</strong> if the statement agrees with the information, <strong>FALSE</strong> if the statement contradicts the information, or <strong>NOT GIVEN</strong> if there is no information on this.</p>',
            'mcq'        => '<p>Choose the correct letter, <strong>A, B, C</strong> or <strong>D</strong>.</p>',
            'headings'   => '<p>Choose the correct heading for each paragraph from the list of headings below and move it into the gap above the paragraph.</p>',
            'matching'   => '<p>Which paragraph contains the following information? Choose the correct letter for each statement.</p>',
            'matching_items' => '<p>Look at the statements below and the list of items. Match each statement with the correct item.</p>',
            'mcq5'       => '<p>Choose <strong>TWO</strong> letters, <strong>A–E</strong>.</p>',
            'matching_names' => '<p>Look at the following statements and the list of people below.</p><p>Match each statement with the correct person, <strong>A, B</strong> or <strong>C</strong>.</p><p>Write the correct letter, <strong>A, B</strong> or <strong>C</strong>, in the boxes on your answer sheet.</p><p><strong>NB</strong> You may use any letter more than once.</p>',
            default      => '',
        };
    }

    /** Listening: attach (or replace) the one audio file of the whole test. */
    public function saveAudio(Request $request, IeltsTest $test)
    {
        $this->guard($test);
        abort_unless($this->module === 'listening', 404);
        $request->validate(['audio' => ['required', 'file', 'mimes:mp3,mpga,wav,m4a,aac,ogg,oga', 'max:204800']], [
            'audio.required' => 'Please choose an audio file.',
            'audio.mimes'    => 'Please choose an MP3, WAV, M4A, AAC or OGG audio file.',
            'audio.max'      => 'The audio file must be smaller than 200 MB.',
        ]);
        $file = $request->file('audio');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'listening';
        $name = Str::limit($base, 40, '') . '-' . Str::random(6) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $dir  = 'uploads/ielts/' . $test->id . '/listening';
        $file->move(public_path($dir), $name);

        $content = $this->content($test);
        $content['audio'] = $dir . '/' . $name;
        $this->store($test, $content);

        return redirect()->route('admin.ielts.listening', $test)->with('success', 'The audio file was saved.');
    }

    public function removeAudio(IeltsTest $test)
    {
        $this->guard($test);
        $content = $this->content($test);
        $content['audio'] = '';
        $this->store($test, $content);

        return redirect()->route('admin.ielts.listening', $test)->with('success', 'The audio file was removed from this test.');
    }

    /** Listening: upload the picture for a Map Labelling question (called from the question form). */
    public function saveImage(Request $request, IeltsTest $test)
    {
        $this->guard($test);
        abort_unless(in_array($this->module, ['listening', 'reading'], true), 404);
        $request->validate(['image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240']], [
            'image.image' => 'Please choose a picture (JPG, PNG, GIF or WebP).',
            'image.mimes' => 'Please choose a picture (JPG, PNG, GIF or WebP).',
            'image.max'   => 'The picture must be smaller than 10 MB.',
        ]);
        $file = $request->file('image');
        $name = 'map-' . Str::random(10) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $dir  = 'uploads/ielts/' . $test->id . '/listening';
        $file->move(public_path($dir), $name);

        return response()->json(['path' => $dir . '/' . $name, 'url' => asset($dir . '/' . $name)]);
    }
}

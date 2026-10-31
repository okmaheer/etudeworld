<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CelpipTest;
use App\Models\VocabSource;
use App\Models\VocabTopic;
use App\Models\VocabWord;
use App\Support\VocabAi;
use Illuminate\Http\Request;

/**
 * Vocabulary Builder admin for CELPIP test sections (Reading / Listening) and for Writing / Speaking topics.
 * The AI suggests words, you review them and tick Approved (same screen as IELTS).
 */
class VocabAdminController extends Controller
{
    /* ───────── Overview of all vocabulary ───────── */

    /** Every list (test section or topic) with its counts: [section title => rows]. */
    public static function sections(): array
    {
        $langs = count(VocabWord::LANGUAGES);
        $stat = [];   // list key => [total, approved, missing translations]
        foreach (VocabWord::select('id', 'ielts_test_id', 'celpip_test_id', 'topic_id', 'module', 'approved', 'translations')->cursor() as $w) {
            $key = $w->topic_id ? "topic:{$w->topic_id}" : ($w->celpip_test_id ? "celpip:{$w->module}:{$w->celpip_test_id}" : "ielts:{$w->module}:{$w->ielts_test_id}");
            $stat[$key] ??= ['total' => 0, 'approved' => 0, 'missing' => 0];
            $stat[$key]['total']++;
            if ($w->approved) $stat[$key]['approved']++;
            if (count(array_filter((array) $w->translations, 'strlen')) < $langs) $stat[$key]['missing']++;
        }
        $row = fn ($key, $label, $open, $translate) => ['key' => $key, 'label' => $label, 'open' => $open, 'translate' => $translate] + ($stat[$key] ?? ['total' => 0, 'approved' => 0, 'missing' => 0]);
        $sections = [];
        foreach (['ielts' => \App\Models\IeltsTest::class, 'celpip' => CelpipTest::class] as $exam => $model) {
            foreach (['listening', 'reading'] as $m) {
                $rows = [];
                foreach ($model::all()->sortBy(fn ($t) => (int) preg_replace('/\D/', '', $t->moduleLabel())) as $t) {
                    $name = $exam === 'celpip' ? $t->name : $t->formatShort() . ' ' . $t->moduleLabel();
                    $rows[] = $row("$exam:$m:{$t->id}", $name, route("admin.$exam.vocab", [$t, $m]), route("admin.$exam.vocab.translate", [$t, $m]));
                }
                $sections[strtoupper($exam) . ' ' . ucfirst($m)] = $rows;
            }
        }
        foreach (VocabTopic::orderBy('exam')->orderBy('skill')->orderBy('name')->get() as $t) {
            $sections[strtoupper($t->exam) . ' ' . ucfirst($t->skill) . ' topics'][] = $row("topic:{$t->id}", $t->name, route('admin.vocab-topics.show', $t), route('admin.vocab-topics.translate', $t));
        }

        return $sections;
    }

    public function overview()
    {
        $sections = static::sections();
        $all = collect($sections)->flatten(1);

        return view('admin.vocab.overview', ['sections' => $sections, 'totals' => ['total' => $all->sum('total'), 'approved' => $all->sum('approved'), 'missing' => $all->sum('missing')]]);
    }

    /* ───────── CELPIP Reading / Listening ───────── */

    public function celpipIndex(CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);
        $text = $this->celpipText($test, $module);

        return view('admin.vocab.words', [
            'title' => 'Vocabulary – ' . $test->name . ' · ' . ucfirst($module),
            'back' => [route('admin.celpip.module', [$test, $module]), '← ' . ucfirst($module) . ' questions'],
            'words' => $this->celpipQuery($test, $module)->orderBy('id')->get(), 'hasKey' => (bool) VocabAi::key()[0], 'hasText' => trim($text) !== '',
            'intro' => 'The AI reads the ' . ($module === 'listening' ? 'transcripts below' : 'passages') . ' and suggests the difficult words, with meanings, synonyms and translations. Nothing is shown to students until you tick Approved.',
            'generateLabel' => 'Find difficult words', 'missing' => 'Add the ' . ($module === 'listening' ? 'transcripts' : 'passages') . ' first.',
            'routes' => $this->celpipRoutes($test, $module),
            'source' => $module === 'listening' ? ['url' => route('admin.celpip.vocab.source', [$test, $module]), 'text' => VocabSource::where(['exam' => 'celpip', 'test_id' => $test->id, 'module' => $module])->value('text')] : null,
        ]);
    }

    public function celpipSource(Request $request, CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);
        abort_unless($module === 'listening', 404);
        VocabSource::updateOrCreate(['exam' => 'celpip', 'test_id' => $test->id, 'module' => $module], ['text' => (string) $request->input('text', '')]);

        return back()->with('success', 'Transcripts saved.');
    }

    public function celpipGenerate(CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);
        [$key, $openai] = VocabAi::key();
        if (! $key) return back()->with('error', 'Add your AI key to the website\'s .env file first.');
        $text = mb_substr($this->celpipText($test, $module), 0, 60000);
        if (trim($text) === '') return back()->with('error', $module === 'listening' ? 'Add the transcripts first.' : 'Add the reading passages first.');
        @set_time_limit(900);
        $picked = VocabAi::ask($key, $openai, VocabAi::textPrompt('CELPIP', $module, $text, $openai));
        if (is_string($picked)) return back()->with('error', $picked);
        $list = array_values(array_filter($picked, fn ($w) => is_array($w) && trim((string) ($w['word'] ?? '')) !== ''));
        VocabAi::translate($key, $openai, $list);
        $q = $this->celpipQuery($test, $module);
        $added = VocabAi::store($list, ['exam' => 'celpip', 'celpip_test_id' => $test->id, 'module' => $module], $q->pluck('word')->map(fn ($w) => mb_strtolower($w))->all());

        return back()->with('success', "$added words were suggested. Please review them and tick Approved.");
    }

    public function celpipTranslate(CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);

        return $this->translateMissing($this->celpipQuery($test, $module));
    }

    public function celpipAdd(Request $request, CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);

        return $this->addWord($request, ['exam' => 'celpip', 'celpip_test_id' => $test->id, 'module' => $module]);
    }

    public function celpipSave(Request $request, CelpipTest $test, string $module)
    {
        $this->guardCelpip($test, $module);

        return $this->saveWords($request, $this->celpipQuery($test, $module));
    }

    /* ───────── Writing / Speaking topics ───────── */

    public function topics()
    {
        return view('admin.vocab.topics', [
            'topics' => VocabTopic::orderBy('exam')->orderBy('skill')->orderBy('name')->get(),
            'counts' => VocabWord::whereNotNull('topic_id')->selectRaw('topic_id, count(*) c, sum(approved) a')->groupBy('topic_id')->get()->keyBy('topic_id'),
        ]);
    }

    public function topicStore(Request $request)
    {
        $d = $request->validate(['exam' => 'required|in:ielts,celpip', 'skill' => 'required|in:writing,speaking', 'name' => 'required|string|max:120']);
        $t = VocabTopic::create($d + ['slug' => VocabTopic::makeSlug($d['exam'], $d['skill'], $d['name']), 'status' => 'active']);

        return redirect()->route('admin.vocab-topics.show', $t)->with('success', 'Topic added. Now click "Generate words".');
    }

    public function topicUpdate(Request $request, VocabTopic $topic)
    {
        $d = $request->validate(['name' => 'required|string|max:120', 'status' => 'required|in:active,draft']);
        $topic->update($d);

        return back()->with('success', 'Topic saved.');
    }

    public function topicDestroy(VocabTopic $topic)
    {
        VocabWord::where('topic_id', $topic->id)->delete();
        $topic->delete();

        return redirect()->route('admin.vocab-topics')->with('success', 'Topic deleted.');
    }

    public function topicShow(VocabTopic $topic)
    {
        return view('admin.vocab.words', [
            'title' => 'Vocabulary topic – ' . $topic->name,
            'back' => [route('admin.vocab-topics'), '← All topics'],
            'words' => VocabWord::where('topic_id', $topic->id)->orderBy('id')->get(), 'hasKey' => (bool) VocabAi::key()[0], 'hasText' => true,
            'intro' => 'The AI writes ' . strtoupper($topic->exam) . ' ' . ucfirst($topic->skill) . ' words, collocations and useful phrases about this topic. Review them and tick Approved; nothing is shown to students before that.',
            'generateLabel' => 'Generate words', 'missing' => '',
            'routes' => ['generate' => route('admin.vocab-topics.generate', $topic), 'translate' => route('admin.vocab-topics.translate', $topic), 'add' => route('admin.vocab-topics.add', $topic), 'save' => route('admin.vocab-topics.save', $topic)],
            'topic' => $topic, 'source' => null,
        ]);
    }

    public function topicGenerate(VocabTopic $topic)
    {
        [$key, $openai] = VocabAi::key();
        if (! $key) return back()->with('error', 'Add your AI key to the website\'s .env file first.');
        @set_time_limit(900);
        $picked = VocabAi::ask($key, $openai, VocabAi::topicPrompt($topic->exam, $topic->skill, $topic->name, $openai));
        if (is_string($picked)) return back()->with('error', $picked);
        $list = array_values(array_filter($picked, fn ($w) => is_array($w) && trim((string) ($w['word'] ?? '')) !== ''));
        if ($topic->exam === 'ielts') $list = array_slice($list, 0, 30);   // IELTS Writing / Speaking: 30 words per topic
        VocabAi::translate($key, $openai, $list);
        $have = VocabWord::where('topic_id', $topic->id)->pluck('word')->map(fn ($w) => mb_strtolower($w))->all();
        $added = VocabAi::store($list, ['exam' => $topic->exam, 'topic_id' => $topic->id, 'module' => $topic->skill], $have);

        return back()->with('success', "$added words were suggested. Please review them and tick Approved.");
    }

    public function topicTranslate(VocabTopic $topic)
    {
        return $this->translateMissing(VocabWord::where('topic_id', $topic->id));
    }

    public function topicAdd(Request $request, VocabTopic $topic)
    {
        return $this->addWord($request, ['exam' => $topic->exam, 'topic_id' => $topic->id, 'module' => $topic->skill]);
    }

    public function topicSave(Request $request, VocabTopic $topic)
    {
        return $this->saveWords($request, VocabWord::where('topic_id', $topic->id));
    }

    /* ───────── shared ───────── */

    private function translateMissing($query)
    {
        [$key, $openai] = VocabAi::key();
        if (! $key) return back()->with('error', 'Add your AI key to the website\'s .env file first.');
        @set_time_limit(900);
        $words = $query->get()->keyBy('id');
        $list = $words->map(fn ($w) => ['word' => $w->word, 'meaning' => (string) $w->meaning, 'translations' => (array) $w->translations])->all();
        VocabAi::translate($key, $openai, $list);
        foreach ($list as $id => $row) $words[$id]->update(['translations' => array_intersect_key($row['translations'], VocabWord::LANGUAGES)]);

        return back()->with('success', 'Missing translations were filled in.');
    }

    private function addWord(Request $request, array $attrs)
    {
        $d = $request->validate(['word' => ['required', 'string', 'max:190']]);
        VocabWord::create($attrs + ['word' => trim($d['word']), 'approved' => false, 'source' => 'manual']);

        return back()->with('success', 'The word was added. Fill in its meaning below.');
    }

    /** Save every row: edits, approve ticks and deletes. */
    private function saveWords(Request $request, $query)
    {
        $rows = (array) $request->input('w', []);
        foreach ($query->get() as $word) {
            $r = $rows[$word->id] ?? null;
            if (! is_array($r)) continue;
            if (! empty($r['delete'])) { $word->delete(); continue; }
            $tr = [];
            foreach (array_keys(VocabWord::LANGUAGES) as $c) if (trim((string) ($r['tr'][$c] ?? '')) !== '') $tr[$c] = trim((string) $r['tr'][$c]);
            $word->update([
                'word' => mb_substr(trim((string) ($r['word'] ?? $word->word)) ?: $word->word, 0, 190), 'type' => mb_substr(trim((string) ($r['type'] ?? '')), 0, 40),
                'meaning' => trim((string) ($r['meaning'] ?? '')), 'example' => trim((string) ($r['example'] ?? '')),
                'synonyms' => array_values(array_filter(array_map('trim', explode(',', (string) ($r['synonyms'] ?? ''))))), 'translations' => $tr,
                'approved' => ! empty($r['approved']),
            ]);
        }

        return back()->with('success', 'Saved.');
    }

    private function guardCelpip(CelpipTest $test, string $module): void
    {
        abort_unless(in_array($module, ['listening', 'reading'], true) && $test->hasModule($module), 404);
    }

    private function celpipQuery(CelpipTest $test, string $module)
    {
        return VocabWord::where('exam', 'celpip')->where('celpip_test_id', $test->id)->where('module', $module);
    }

    private function celpipRoutes(CelpipTest $test, string $module): array
    {
        return ['generate' => route('admin.celpip.vocab.generate', [$test, $module]), 'translate' => route('admin.celpip.vocab.translate', [$test, $module]),
                'add' => route('admin.celpip.vocab.add', [$test, $module]), 'save' => route('admin.celpip.vocab.save', [$test, $module])];
    }

    /** The passages (Reading) or the pasted transcripts (Listening) as plain text. */
    private function celpipText(CelpipTest $test, string $module): string
    {
        if ($module === 'listening') {
            return trim((string) VocabSource::where(['exam' => 'celpip', 'test_id' => $test->id, 'module' => 'listening'])->value('text'));
        }
        $plain = fn ($h) => trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|div|h\d|li|tr)>/i', "\n", (string) $h))));
        $out = [];
        foreach ((array) ($test->moduleContent('reading')['parts'] ?? []) as $part) {
            $out[] = $plain($part['passage'] ?? '');
            foreach ((array) ($part['sections'] ?? []) as $sec) {
                $out[] = $plain($sec['passage'] ?? '');
                foreach ((array) ($sec['questions'] ?? []) as $q) $out[] = $plain($q['statement'] ?? '');
            }
        }

        return trim(implode("\n\n", array_filter($out)));
    }
}

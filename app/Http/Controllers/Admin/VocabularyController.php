<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IeltsTest;
use App\Models\VocabWord;
use Illuminate\Http\Request;

/** Vocabulary Builder admin: the AI suggests difficult words from the passages / transcripts; you review and approve them. */
class VocabularyController extends Controller
{
    public function index(IeltsTest $test, string $module)
    {
        $this->guard($test, $module);
        $words = VocabWord::where('ielts_test_id', $test->id)->where('module', $module)->orderBy('id')->get();

        return view('admin.ielts.vocabulary', ['test' => $test, 'module' => $module, 'words' => $words, 'hasKey' => (bool) (config('services.openai.key') ?: config('services.anthropic.key')), 'hasText' => trim($this->sourceText($test, $module)) !== '']);
    }

    /** Ask the AI for difficult words and add them as "not approved yet". */
    public function generate(IeltsTest $test, string $module)
    {
        $this->guard($test, $module);
        $openai = config('services.openai.key');
        $key = $openai ?: config('services.anthropic.key');
        if (! $key) return back()->with('error', 'Add your AI key to the website\'s .env file first (see the note on this page).');
        $text = mb_substr($this->sourceText($test, $module), 0, 60000);
        if (trim($text) === '') return back()->with('error', $module === 'listening' ? 'Add the transcripts first.' : 'Add the reading passages first.');

        @set_time_limit(900);
        $note = $module === 'listening' ? ' This is a spoken text, so include everyday spoken phrases, idioms and phrasal verbs that students may fail to catch or understand.' : '';
        $picked = \App\Support\VocabAi::ask($key, $openai, "You help IELTS students with vocabulary. From the IELTS " . ucfirst($module) . " text below, pick 80 to 100 items of CEFR level B1 to C2 that students are likely to find difficult "
            . "(skip A1-A2 words, names and numbers; pick across all parts of the text). IMPORTANT: at least 40 of the items must be multi-word expressions copied exactly from the text - "
            . "collocations (e.g. \"raise awareness\"), phrasal verbs (e.g. \"carry out\"), idioms, fixed phrases and useful sentence chunks (e.g. \"on the whole\", \"as far as I can tell\"). The rest can be single words.$note "
            . "For each give: \"word\" (the word or phrase exactly as in the text), \"type\" (noun, verb, adjective, collocation, phrasal verb, idiom, phrase...), "
            . "\"level\" (B1, B2, C1 or C2), \"meaning\" (one simple English sentence), \"synonyms\" (array of 2-3), \"example\" (a new short example sentence, not copied from the text).\n"
            . "Reply with ONLY " . ($openai ? 'a JSON object like {"words": [ ... ]}' : 'a JSON array') . ", no other text.\n\nTEXT:\n" . $text);
        if (is_string($picked)) return back()->with('error', $picked);

        $list = array_values(array_filter($picked, fn ($w) => is_array($w) && trim((string) ($w['word'] ?? '')) !== ''));
        \App\Support\VocabAi::translate($key, $openai, $list);

        $have = VocabWord::where('ielts_test_id', $test->id)->where('module', $module)->pluck('word')->map(fn ($w) => mb_strtolower($w))->all();
        $added = 0;
        foreach ($list as $w) {
            $word = trim((string) ($w['word'] ?? ''));
            if ($word === '' || in_array(mb_strtolower($word), $have, true)) continue;
            VocabWord::create([
                'ielts_test_id' => $test->id, 'module' => $module, 'word' => mb_substr($word, 0, 190), 'type' => mb_substr(trim((string) ($w['type'] ?? '') . (preg_match('/^(B1|B2|C1|C2)$/', (string) ($w['level'] ?? '')) ? ', ' . $w['level'] : ''), ', '), 0, 40),
                'meaning' => (string) ($w['meaning'] ?? ''), 'synonyms' => array_slice(array_map('strval', (array) ($w['synonyms'] ?? [])), 0, 5),
                'example' => (string) ($w['example'] ?? ''), 'translations' => array_intersect_key(array_map('strval', (array) ($w['translations'] ?? [])), VocabWord::LANGUAGES),
                'approved' => false, 'source' => 'auto',
            ]);
            $have[] = mb_strtolower($word);
            $added++;
        }

        return back()->with('success', "$added words were suggested. Please review them and tick Approved.");
    }

    public function add(Request $request, IeltsTest $test, string $module)
    {
        $this->guard($test, $module);
        $data = $request->validate(['word' => ['required', 'string', 'max:190']]);
        VocabWord::create(['ielts_test_id' => $test->id, 'module' => $module, 'word' => trim($data['word']), 'approved' => false, 'source' => 'manual']);

        return back()->with('success', 'The word was added. Fill in its meaning below.');
    }

    /** Save every row: edits, approve ticks and deletes. */
    public function save(Request $request, IeltsTest $test, string $module)
    {
        $this->guard($test, $module);
        $rows = (array) $request->input('w', []);
        foreach (VocabWord::where('ielts_test_id', $test->id)->where('module', $module)->get() as $word) {
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

    /** Fill the missing translations of words already saved for this test. */
    public function translateMissing(IeltsTest $test, string $module)
    {
        $this->guard($test, $module);
        $openai = config('services.openai.key');
        $key = $openai ?: config('services.anthropic.key');
        if (! $key) return back()->with('error', 'Add your AI key to the website\'s .env file first.');
        @set_time_limit(900);
        $words = VocabWord::where('ielts_test_id', $test->id)->where('module', $module)->get()->keyBy('id');
        $list = $words->map(fn ($w) => ['word' => $w->word, 'meaning' => (string) $w->meaning, 'translations' => (array) $w->translations])->all();
        \App\Support\VocabAi::translate($key, $openai, $list);
        foreach ($list as $id => $row) $words[$id]->update(['translations' => array_intersect_key($row['translations'], VocabWord::LANGUAGES)]);

        return back()->with('success', 'Missing translations were filled in.');
    }

    private function guard(IeltsTest $test, string $module): void
    {
        abort_unless($test->hasModule($module), 404);
    }

    /** All passages (Reading) or transcripts (Listening) of the test as plain text. */
    private function sourceText(IeltsTest $test, string $module): string
    {
        $cls = $module === 'listening' ? \App\Support\IeltsListening::class : \App\Support\IeltsReading::class;
        $content = $cls::normalize($test->moduleContent($module));
        $out = [];
        foreach ($content['passages'] as $p) $out[] = trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|div|h\d|li)>/i', "\n", (string) $p))));

        return trim(implode("\n\n", array_filter($out)));
    }
}

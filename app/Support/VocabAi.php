<?php

namespace App\Support;

use App\Models\VocabWord;
use Illuminate\Support\Facades\Http;

/** The AI side of the Vocabulary Builder (shared by IELTS, CELPIP and the topic lists). */
class VocabAi
{
    public static function key(): array
    {
        $openai = config('services.openai.key');

        return [$openai ?: config('services.anthropic.key'), (bool) $openai];
    }

    /** One AI request that must answer with JSON. Returns the decoded list, or a plain error message (string). */
    public static function ask(string $key, bool|string|null $openai, string $prompt): array|string
    {
        try {
            $res = $openai
                ? Http::withToken($key)->timeout(180)->post(rtrim(config('services.openai.base'), '/') . '/chat/completions', ['model' => config('services.openai.model'), 'response_format' => ['type' => 'json_object'], 'messages' => [['role' => 'user', 'content' => $prompt]]])
                : Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(180)
                    ->post('https://api.anthropic.com/v1/messages', ['model' => config('services.anthropic.model'), 'max_tokens' => 16000, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        } catch (\Throwable $e) {
            return 'Could not reach the AI service. Please try again.';
        }
        if (! $res->successful()) return 'The AI service returned an error (' . $res->status() . '). Check your API key and credit.';
        $raw = (string) ($openai ? $res->json('choices.0.message.content') : $res->json('content.0.text'));
        $list = json_decode(preg_replace('/^```(?:json)?|```$/m', '', trim($raw)), true);
        if (is_array($list) && isset($list['words'])) $list = $list['words'];

        return is_array($list) ? $list : 'The AI answer could not be read. Please try again.';
    }

    /** Fill in the missing translations of $list (each item: word, meaning, translations). Small batches, two passes. */
    public static function translate(string $key, bool|string|null $openai, array &$list): void
    {
        $langs = collect(VocabWord::LANGUAGES)->map(fn ($n, $c) => "\"$c\" ($n)")->implode(', ');
        for ($pass = 0; $pass < 2; $pass++) {
            $todo = array_keys(array_filter($list, fn ($w) => count(array_filter((array) ($w['translations'] ?? []), 'strlen')) < count(VocabWord::LANGUAGES)));
            foreach (array_chunk($todo, 10) as $ids) {
                $items = array_map(fn ($i) => ['i' => $i, 'word' => $list[$i]['word'], 'meaning' => (string) ($list[$i]['meaning'] ?? '')], $ids);
                $tr = static::ask($key, $openai, "Translate each English word or phrase below (in the sense of its meaning) into ALL of these languages: $langs. Every item needs every language. Keep each translation short (the equivalent word or phrase). "
                    . "Reply with ONLY " . ($openai ? 'a JSON object like {"words": [ ... ]}' : 'a JSON array') . " with one object per item: {\"i\": same number, \"pa\": \"...\", \"ur\": \"...\", ...} using the language codes as keys.\n\n" . json_encode($items, JSON_UNESCAPED_UNICODE));
                if (! is_array($tr)) continue;
                foreach ($tr as $row) {
                    if (! is_array($row) || ! isset($list[$row['i'] ?? -1])) continue;
                    $new = array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : '', array_intersect_key($row, VocabWord::LANGUAGES)), 'strlen');
                    $list[$row['i']]['translations'] = $new + array_filter((array) ($list[$row['i']]['translations'] ?? []), 'strlen');
                }
            }
        }
    }

    /** What to ask for each item (shared by every prompt). */
    private const FIELDS = 'For each give: "word" (the word or phrase), "type" (noun, verb, adjective, collocation, phrasal verb, idiom, phrase...), "level" (B1, B2, C1 or C2), "meaning" (one simple English sentence), "synonyms" (array of 2-3), "example" (a new short example sentence).';

    /** Prompt: pick difficult words from a CELPIP Reading passage or Listening transcript. */
    public static function textPrompt(string $exam, string $skill, string $text, bool $json): string
    {
        $note = $skill === 'listening' ? ' This is a spoken text, so include everyday spoken phrases, idioms and phrasal verbs that students may fail to catch or understand.' : '';

        return "You help $exam students with vocabulary. From the $exam " . ucfirst($skill) . " text below, pick 80 to 100 items of CEFR level B1 to C2 that students are likely to find difficult "
            . "(skip A1-A2 words, names and numbers; pick across all parts of the text). IMPORTANT: at least 40 of the items must be multi-word expressions copied exactly from the text - "
            . "collocations, phrasal verbs, idioms, fixed phrases and useful sentence chunks. The rest can be single words.$note "
            . self::FIELDS . " The \"word\" must be exactly as in the text; the example must be new, not copied from the text.\n"
            . 'Reply with ONLY ' . ($json ? 'a JSON object like {"words": [ ... ]}' : 'a JSON array') . ", no other text.\n\nTEXT:\n" . $text;
    }

    /** Prompt: words for a topic (Writing or Speaking), IELTS or CELPIP style. */
    public static function topicPrompt(string $exam, string $skill, string $topic, bool $json): string
    {
        $examName = $exam === 'celpip' ? 'CELPIP' : 'IELTS';
        if ($skill === 'writing') {
            $how = $exam === 'celpip'
                ? 'formal and semi-formal words, polite email phrases, linking words, useful collocations and phrases for CELPIP Writing Task 1 (emails: requests, complaints, thanks, advice) and Task 2 (survey responses and opinions)'
                : 'formal academic words, linking and cohesive phrases, Task 1 trend and comparison language (for Academic: graphs, charts, processes, maps; for General Training: letters), Task 2 argument and opinion phrases, and Band 7+ collocations';
        } else {
            $how = $exam === 'celpip'
                ? 'natural spoken words and phrases, idioms, fillers and phrases for giving advice, describing scenes, making predictions, comparing, and expressing opinions in CELPIP Speaking tasks'
                : 'natural spoken words and phrases, idioms, phrasal verbs, fillers and answer starters for IELTS Speaking Part 1, Part 2 (long talk) and Part 3 (discussion)';
        }

        // IELTS Writing / Speaking: exactly 30 words per topic. CELPIP keeps the longer list.
        [$count, $multi] = $exam === 'ielts' ? ['exactly 30', 15] : ['60 to 80', 30];

        return "You help $examName students with vocabulary for the " . ucfirst($skill) . " test. Topic: \"$topic\". Give $count useful items of CEFR level B1 to C2 about this topic: $how. "
            . "At least $multi items must be multi-word expressions (collocations, phrasal verbs, idioms, fixed phrases). Do not repeat items. "
            . self::FIELDS . " The example must be a natural sentence that a student could use in the " . ucfirst($skill) . " test.\n"
            . 'Reply with ONLY ' . ($json ? 'a JSON object like {"words": [ ... ]}' : 'a JSON array') . ', no other text.';
    }

    /** Save the AI's list as "not approved yet" words, skipping any word already in $have (lower-case). Returns how many were added. */
    public static function store(array $list, array $attrs, array $have): int
    {
        $added = 0;
        foreach ($list as $w) {
            $word = trim((string) ($w['word'] ?? ''));
            if ($word === '' || in_array(mb_strtolower($word), $have, true)) continue;
            VocabWord::create($attrs + [
                'word' => mb_substr($word, 0, 190),
                'type' => mb_substr(trim((string) ($w['type'] ?? '') . (preg_match('/^(B1|B2|C1|C2)$/', (string) ($w['level'] ?? '')) ? ', ' . $w['level'] : ''), ', '), 0, 40),
                'meaning' => (string) ($w['meaning'] ?? ''), 'synonyms' => array_slice(array_map('strval', (array) ($w['synonyms'] ?? [])), 0, 5),
                'example' => (string) ($w['example'] ?? ''), 'translations' => array_intersect_key(array_map('strval', (array) ($w['translations'] ?? [])), VocabWord::LANGUAGES),
                'approved' => false, 'source' => 'auto',
            ]);
            $have[] = mb_strtolower($word);
            $added++;
        }

        return $added;
    }
}

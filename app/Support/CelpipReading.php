<?php

namespace App\Support;

/**
 * Everything about the CELPIP Reading test content.
 *
 * Saved in celpip_test_modules.content (module = "reading") like this:
 *
 * intro                    Main Section: text + audio (filled in by default)
 * parts.{1-4}.passage      the passage shown on the left (text, pictures, tables)
 * parts.{1-4}.sections[]   question sections shown on the right, in order. Each one:
 *     type          mcq (#id-31) | blanks (#id-33) | matching (#id-37)
 *     instructions  text shown above all its questions
 *     passage       blanks only: text where every "#" becomes a drop-down question
 *     questions[]   statement (not used for blanks), options[] (any number), correct (index or null)
 */
class CelpipReading
{
    /** The four reading parts and their time limits (minutes). */
    public const PARTS = [
        1 => ['name' => 'Reading Correspondence',      'minutes' => 10],
        2 => ['name' => 'Reading to Apply a Diagram',  'minutes' => 9],
        3 => ['name' => 'Reading for Information',     'minutes' => 10],
        4 => ['name' => 'Reading for Viewpoints',      'minutes' => 13],
    ];

    /** Question section types. */
    public const TYPES = [
        'mcq'      => ['label' => 'Multiple Choice Questions', 'id' => 31],
        'blanks'   => ['label' => 'Fill in the Blanks',        'id' => 33],
        'matching' => ['label' => 'Matching Statements',       'id' => 37],
    ];

    /** Instructions a new section starts with (the admin can change them). */
    public const DEFAULT_INSTRUCTIONS = [
        'mcq'      => '<p>Using the drop-down menu, choose the best option according to the information given in the message.</p>',
        'blanks'   => '<p>Here is a response to the message. Complete the response by filling in the blanks. Select the best choice for each blank from the drop-down menu.</p>',
        'matching' => '<p>Decide which paragraph, A to D, has the information given in each statement below. Select E if the information is not given in any of the paragraphs.</p>',
    ];

    public static function defaultIntroText(): string
    {
        return '<h3>Reading Test Instructions</h3><ol>'
            . '<li><strong>On the official test, once you leave a page, you cannot go back to it to change your answers. However, in this sample test, you can.</strong></li>'
            . '<li><strong>Watch the timer in the top right corner to make sure that you complete the Reading Test before the time is up. For more information on test format, click <a href="https://www.celpip.ca/take-celpip/test-format/" target="_blank" rel="noopener">here</a>.</strong></li>'
            . '<li><strong>This Reading Test is identical in format to the official test except that the Reading section of the official test may be slightly longer as it might contain additional questions included for research and development purposes.</strong></li>'
            . '</ol>';
    }

    /** What the edit page shows: saved content, with defaults for anything never saved. */
    public static function forEditing(?array $saved): array
    {
        $saved ??= [];
        $out = [
            'intro' => [
                'text'  => array_key_exists('text', $saved['intro'] ?? []) ? (string) $saved['intro']['text'] : self::defaultIntroText(),
                'audio' => array_key_exists('audio', $saved['intro'] ?? []) ? (string) $saved['intro']['audio'] : '',
            ],
            'parts' => [],
        ];
        foreach (array_keys(self::PARTS) as $p) {
            $part = $saved['parts'][$p] ?? $saved['parts'][(string) $p] ?? [];
            $out['parts'][$p] = [
                'passage'  => (string) ($part['passage'] ?? ''),
                'sections' => self::cleanSections($part['sections'] ?? []),
            ];
        }

        return $out;
    }

    /** Clean what the edit page sends before it is saved. */
    public static function fromInput(array $input): array
    {
        $audio = trim((string) ($input['intro']['audio'] ?? ''));
        if ($audio !== '' && ! preg_match('#^(uploads/celpip/\d+/|audio/celpip/)[\w./-]+$#', $audio)) {
            $audio = '';
        }
        $out = [
            'intro' => ['text' => (string) ($input['intro']['text'] ?? ''), 'audio' => $audio],
            'parts' => [],
        ];
        foreach (array_keys(self::PARTS) as $p) {
            $part = $input['parts'][$p] ?? $input['parts'][(string) $p] ?? [];
            $out['parts'][$p] = [
                'passage'  => (string) ($part['passage'] ?? ''),
                'sections' => self::cleanSections(is_array($part['sections'] ?? null) ? $part['sections'] : []),
            ];
        }

        return $out;
    }

    private static function cleanSections(array $sections): array
    {
        $out = [];
        foreach (array_slice($sections, 0, 30) as $s) {
            if (! is_array($s) || ! isset(self::TYPES[$s['type'] ?? ''])) continue;
            $questions = [];
            foreach (array_slice(is_array($s['questions'] ?? null) ? $s['questions'] : [], 0, 60) as $q) {
                if (! is_array($q)) continue;
                $options = array_values(array_map(fn ($o) => trim((string) $o), array_slice(is_array($q['options'] ?? null) ? $q['options'] : [], 0, 12)));
                $correct = $q['correct'] ?? null;
                $correct = is_numeric($correct) && (int) $correct >= 0 && (int) $correct < count($options) ? (int) $correct : null;
                $questions[] = ['statement' => trim((string) ($q['statement'] ?? '')), 'options' => $options, 'correct' => $correct];
            }
            $out[] = [
                'type'         => $s['type'],
                'instructions' => (string) ($s['instructions'] ?? ''),
                'passage'      => $s['type'] === 'blanks' ? (string) ($s['passage'] ?? '') : '',
                'questions'    => $questions,
            ];
        }

        return $out;
    }

    // ───────────────────────── status ─────────────────────────

    /** Complete = (a statement, unless it's a blank) + at least 2 filled options + a correct answer. */
    public static function questionComplete(array $q, string $type): bool
    {
        $filled = array_filter($q['options'] ?? [], fn ($o) => $o !== '');

        return ($type === 'blanks' || filled($q['statement'] ?? ''))
            && count($filled) >= 2
            && $q['correct'] !== null && ($q['options'][$q['correct']] ?? '') !== '';
    }

    /** ['done' => 3, 'total' => 11] for one part. */
    public static function partProgress(array $content, int $part): array
    {
        $done = $total = 0;
        foreach ($content['parts'][$part]['sections'] ?? [] as $s) {
            foreach ($s['questions'] as $q) {
                $total++;
                if (self::questionComplete($q, $s['type'])) $done++;
            }
        }

        return ['done' => $done, 'total' => $total];
    }

    public static function hasQuestions(?array $content): bool
    {
        foreach ($content['parts'] ?? [] as $part) {
            foreach ($part['sections'] ?? [] as $s) {
                foreach ($s['questions'] ?? [] as $q) {
                    if (filled($q['statement'] ?? '') || array_filter($q['options'] ?? [], fn ($o) => $o !== '')) return true;
                }
            }
        }

        return false;
    }

    // ───────────────────────── student screens (frontend) ─────────────────────────

    /**
     * What the student side needs: the instructions screen, then one screen per part
     * (passage on the left, question sections on the right, a timer for the part).
     */
    public static function screens(array $saved): array
    {
        $c = self::forEditing($saved);
        $intro = $c['intro'];
        $out = [
            'intro' => ['text' => $intro['text'], 'audio' => $intro['audio'] !== '' ? asset($intro['audio']) : null],
            'parts' => [],
        ];
        foreach (self::PARTS as $p => $info) {
            $part = $c['parts'][$p];
            $sections = array_values(array_filter($part['sections'], fn ($s) => count($s['questions']) > 0));
            if (! $sections) continue;
            $out['parts'][] = [
                'part'     => $p,
                'title'    => 'Reading Part ' . $p . ': ' . $info['name'],
                'minutes'  => $info['minutes'],
                'passage'  => $part['passage'],
                'sections' => array_map(fn ($s) => [
                    'type'         => $s['type'],
                    'instructions' => $s['instructions'],
                    'passage'      => $s['passage'],
                    'questions'    => array_map(fn ($q) => [
                        'statement' => $q['statement'],
                        'options'   => array_values(array_filter($q['options'], fn ($o) => $o !== '')),
                        // index among the non-empty options
                        'correct'   => $q['correct'] === null || ($q['options'][$q['correct']] ?? '') === '' ? null
                            : count(array_filter(array_slice($q['options'], 0, $q['correct']), fn ($o) => $o !== '')),
                    ], $s['questions']),
                ], $sections),
            ];
        }

        return $out;
    }
}

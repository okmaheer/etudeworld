<?php

namespace App\Support;

/**
 * Everything about the CELPIP Listening test content:
 *  - the default texts/audio every test starts with,
 *  - the fixed layout of each part (how many sections, divisions and questions),
 *  - cleaning up what the admin form sends before it is saved.
 *
 * Saved in celpip_test_modules.content (module = "listening") like this:
 *
 * intro            Main Section (#id-34): text (#intro) + audio (#intro2)
 * parts.1.s1       Section 1 (#id-part-1-intro-56): text + audio          ← defaults
 * parts.1.s2       Section 2 (#id-part-1-q-intro-78): text + audio + image
 * parts.1.divisions[0..2]   Section 3, Divisions A/B/C: text + audio + questions
 *     questions[]  text, statement, options[4] (text + image), correct (0–3), audio, image
 */
class CelpipListening
{
    /** The six listening parts (all six can be edited). */
    public const PARTS = [
        1 => 'Listening to Problem Solving',
        2 => 'Listening to a Daily Life Conversation',
        3 => 'Listening for Information',
        4 => 'Listening to a News Item',
        5 => 'Listening to a Discussion',
        6 => 'Listening for Viewpoints',
    ];

    /** Part 1, Section 3: three divisions with 3, 3 and 2 questions (8 questions in total). */
    public const PART1_DIVISIONS = [
        ['key' => 'A', 'label' => 'i',   'question_ids' => [80, 81, 82]],
        ['key' => 'B', 'label' => 'ii',  'question_ids' => [83, 84, 85]],
        ['key' => 'C', 'label' => 'iii', 'question_ids' => [86, 87]],
    ];

    /** Part 2, Section 3: one conversation followed by 5 questions. */
    public const PART2_DIVISIONS = [
        ['key' => 'A', 'label' => 'i', 'question_ids' => [80, 81, 82, 83, 84]],
    ];

    /** Part 3, Section 3: one conversation followed by 6 questions. */
    public const PART3_DIVISIONS = [
        ['key' => 'A', 'label' => 'i', 'question_ids' => [80, 81, 82, 83, 84, 85]],
    ];

    /** Part 4, Section 3: one news item, then 5 questions shown together (drop-downs, no question audio). */
    public const PART4_DIVISIONS = [
        ['key' => 'A', 'label' => 'i', 'question_ids' => [80, 81, 82, 83, 84]],
    ];

    /** Part 5, Section 3: one discussion, then 8 questions shown together (drop-downs, no question audio). */
    public const PART5_DIVISIONS = [
        ['key' => 'A', 'label' => 'i', 'question_ids' => [80, 81, 82, 83, 84, 85, 86, 87]],
    ];

    /** Part 6, Section 3: one report, then 6 questions shown together (drop-downs, no question audio). */
    public const PART6_DIVISIONS = [
        ['key' => 'A', 'label' => 'i', 'question_ids' => [80, 81, 82, 83, 84, 85]],
    ];

    /**
     * Parts whose questions are all shown on ONE screen with one timer (no audio per question).
     * seconds = total time for all the questions.
     */
    public const GROUP_PARTS = [
        4 => ['seconds' => 180, 'intro' => '<p>Choose the best way to complete each statement from the drop-down menu.</p>'],
        5 => ['seconds' => 240, 'intro' => '<p>Choose the best answer to each question from the drop-down menu.</p>'],
        6 => ['seconds' => 240, 'intro' => '<p>Choose the best way to answer each question from the drop-down menu.</p>',
              // Section 3's text (shown above the recording), filled in by default
              'recording_text' => '<p>Listen to the following report. You will hear the report only once. It is about 3 minutes long.</p>'],
    ];

    public const DEFAULT_GROUP_INSTRUCTIONS = '<p>Choose the best way to complete each statement from the drop-down menu.</p>';

    /** Parts that have been built so far, with their conversation layout. */
    public const PART_DIVISIONS = [
        1 => self::PART1_DIVISIONS,
        2 => self::PART2_DIVISIONS,
        3 => self::PART3_DIVISIONS,
        4 => self::PART4_DIVISIONS,
        5 => self::PART5_DIVISIONS,
        6 => self::PART6_DIVISIONS,
    ];

    /** Parts whose Section 2 (question intro) also has a picture. Parts 3 and 4 have text + audio only. */
    public const S2_IMAGE_PARTS = [1, 2];

    public const OPTIONS_PER_QUESTION = 4;

    /** Default audio files (shipped with the website in public/audio/celpip). */
    public const DEFAULT_INTRO_AUDIO = 'audio/celpip/listening-instructions.mp3';
    public const DEFAULT_PART1_AUDIO = 'audio/celpip/listening-part-1-intro.mp3';
    /** Default Section 1 audio of each part ('' = none yet; put the file in public/audio/celpip and add it here). */
    public const DEFAULT_PART_AUDIO = [
        1 => self::DEFAULT_PART1_AUDIO,
        2 => '',
        3 => '',
        4 => '',
        5 => '',
        6 => '',
    ];

    public static function defaultIntroText(): string
    {
        return '<h3>Listening Test Instructions</h3><ol>'
            . '<li><strong>On the official test, once you leave a page, you cannot go back to it to change your answers. However, in this sample test, you can.</strong></li>'
            . '<li><strong>For more information on test format, click <a href="https://www.celpip.ca/take-celpip/test-format/" target="_blank" rel="noopener">here</a>.</strong></li>'
            . '<li><strong>Please note that the order of question types on the official test may differ from the order presented here.</strong></li>'
            . '<li><strong>This Listening Test is identical in format to the official test except that the Listening section of the official test may be slightly longer as it might contain additional questions included for research and development purposes.</strong></li>'
            . '</ol>';
    }

    public static function defaultPart1Text(): string
    {
        return '<h3>Listening to Problem Solving</h3><ul>'
            . '<li>You will hear a conversation in 3 sections. You will hear each section only once.</li>'
            . '<li>After each section, you will hear 2 or 3 questions. You will hear the questions only once.</li>'
            . '<li>Choose the best answer to each question.</li>'
            . '</ul>';
    }

    public static function defaultPart2Text(): string
    {
        return '<h3>Listening to a Daily Life Conversation</h3><ul>'
            . '<li>You will hear a conversation followed by 5 questions.</li>'
            . '<li>Listen to each question. You will hear the question only once.</li>'
            . '<li>Choose the best answer to each question.</li>'
            . '</ul>';
    }

    public static function defaultPart3Text(): string
    {
        return '<h3>Listening for Information</h3><ul>'
            . '<li>You will hear a conversation followed by 6 questions.</li>'
            . '<li>Listen to each question. You will hear the question only once.</li>'
            . '<li>Choose the best answer to each question.</li>'
            . '</ul>';
    }

    public static function defaultPart4Text(): string
    {
        return '<h3>Listening to a News Item</h3><ol>'
            . '<li>You will hear a news item once. It is about 1.5 minutes long.</li>'
            . '<li>Then 5 questions will appear.</li>'
            . '<li>Choose the best way to complete each statement from the drop-down menu.</li>'
            . '</ol>';
    }

    public static function defaultPart5Text(): string
    {
        return '<h3>Listening to a Discussion</h3><ol>'
            . '<li>You will hear a discussion once. It is about 1.5 to 2 minutes long.</li>'
            . '<li>Then 8 questions will appear.</li>'
            . '<li>Choose the best answer to each question from the drop-down menu.</li>'
            . '</ol>';
    }

    public static function defaultPart6Text(): string
    {
        return '<h3>Listening for Viewpoints</h3><ol>'
            . '<li>You will hear a report once. It is about 3 minutes long.</li>'
            . '<li>Then 6 questions will appear.</li>'
            . '<li>Choose the best way to answer each question from the drop-down menu.</li>'
            . '</ol>';
    }

    /** The line shown above all the questions of a Part 4/5 question screen (by default). */
    public static function groupIntro(int $part): string
    {
        return self::GROUP_PARTS[$part]['intro'] ?? self::DEFAULT_GROUP_INSTRUCTIONS;
    }

    public static function isGroupPart(int $part): bool
    {
        return isset(self::GROUP_PARTS[$part]);
    }

    public static function defaultPartText(int $part): string
    {
        return match ($part) {
            1 => self::defaultPart1Text(),
            2 => self::defaultPart2Text(),
            3 => self::defaultPart3Text(),
            4 => self::defaultPart4Text(),
            5 => self::defaultPart5Text(),
            6 => self::defaultPart6Text(),
            default => '',
        };
    }

    // ───────────────────────── layout ─────────────────────────

    public static function blankQuestion(): array
    {
        return [
            'text'      => '',
            'statement' => '',
            'options'   => array_fill(0, self::OPTIONS_PER_QUESTION, ['text' => '', 'image' => '']),
            'correct'   => '',
            'audio'     => '',
            'image'     => '',
        ];
    }

    /** The full empty shape. With $withDefaults, default texts/audio are filled in. */
    public static function skeleton(bool $withDefaults = true): array
    {
        $parts = [];
        foreach (self::PART_DIVISIONS as $part => $layout) {
            $parts[$part] = [
                's1' => [
                    'text'  => $withDefaults ? self::defaultPartText($part) : '',
                    'audio' => $withDefaults ? (self::DEFAULT_PART_AUDIO[$part] ?? '') : '',
                ],
                's2' => ['text' => '', 'audio' => '', 'image' => ''],
                'divisions' => array_map(fn ($d) => [
                    'text'      => $withDefaults ? (self::GROUP_PARTS[$part]['recording_text'] ?? '') : '',
                    'audio'     => '',
                    'questions' => array_fill(0, count($d['question_ids']), self::blankQuestion()),
                ] + (self::isGroupPart($part)
                    // the line shown above all the questions on the question screen
                    ? ['questions_intro' => $withDefaults ? self::groupIntro($part) : '']
                    : []), $layout),
            ];
        }

        return [
            'intro' => [
                'text'  => $withDefaults ? self::defaultIntroText() : '',
                'audio' => $withDefaults ? self::DEFAULT_INTRO_AUDIO : '',
            ],
            'parts' => $parts,
        ];
    }

    /**
     * What the edit page shows: saved content laid over the defaults.
     * Anything never saved shows its default; anything saved (even emptied) keeps the saved value.
     */
    public static function forEditing(?array $saved): array
    {
        return self::overlay(self::skeleton(true), $saved ?? []);
    }

    /** Clean what the form sent: only known fields, all as text. */
    public static function fromRequest(array $input): array
    {
        return self::overlay(self::skeleton(false), $input, true);
    }

    /**
     * Copy values from $data into the shape of $shape (unknown keys are dropped).
     * $strict: missing values become empty (used when saving the form).
     */
    private static function overlay(array $shape, array $data, bool $strict = false): array
    {
        $out = [];
        foreach ($shape as $key => $default) {
            $has = is_array($data) && array_key_exists($key, $data);
            $value = $has ? $data[$key] : null;
            if (is_array($default)) {
                $out[$key] = self::overlay($default, is_array($value) ? $value : [], $strict);
            } elseif ($has && ! is_array($value)) {
                $out[$key] = trim((string) $value);
            } else {
                $out[$key] = $strict ? '' : $default;
            }
        }

        return $out;
    }

    // ───────────────────────── status ─────────────────────────

    /** A question is complete when it has a question, 4 options, a correct answer and its audio. */
    public static function questionComplete(array $q, int $part = 1): bool
    {
        $optionsOk = collect($q['options'] ?? [])->every(fn ($o) => filled($o['text'] ?? '') || filled($o['image'] ?? ''));
        $hasQuestion = filled($q['statement'] ?? '') || filled(trim(strip_tags($q['text'] ?? '')));
        // Part 4 questions have no audio of their own
        $audioOk = self::isGroupPart($part) || filled($q['audio'] ?? '');

        return $hasQuestion && $optionsOk && ($q['correct'] ?? '') !== '' && $audioOk;
    }

    /** ['done' => 5, 'total' => 8] for one part. */
    public static function partProgress(array $content, int $part): array
    {
        $questions = collect($content['parts'][$part]['divisions'] ?? [])->flatMap(fn ($d) => $d['questions'] ?? []);

        return [
            'done'  => $questions->filter(fn ($q) => self::questionComplete($q, $part))->count(),
            'total' => collect(self::PART_DIVISIONS[$part] ?? [])->sum(fn ($d) => count($d['question_ids'])),
        ];
    }

    public static function part1Progress(array $content): array
    {
        return self::partProgress($content, 1);
    }

    /** Has the admin added any real questions yet, in any part? (Defaults alone don't count.) */
    public static function hasQuestions(?array $content): bool
    {
        return collect($content['parts'] ?? [])
            ->flatMap(fn ($p) => is_array($p) ? ($p['divisions'] ?? []) : [])
            ->flatMap(fn ($d) => $d['questions'] ?? [])
            ->contains(fn ($q) => filled($q['statement'] ?? '') || filled($q['audio'] ?? '') || filled(trim(strip_tags($q['text'] ?? ''))));
    }

    // ───────────────────────── student screens (frontend) ─────────────────────────

    /**
     * Every screen the student sees, in order, for the whole Listening test (all parts on one page).
     * Types: info (text + picture, audio plays in the background), recording (audio shown in the middle),
     * question (audio + 4 options + 30-second answer timer) and pause (10-second gap).
     * To add Part 2 later, add its screens in partScreens().
     */
    public static function screens(array $content): array
    {
        $content = self::forEditing($content);
        $screens = [];
        if (self::filledHtml($content['intro']['text']) || filled($content['intro']['audio'])) {
            $screens[] = self::info('CELPIP Listening Test', $content['intro']);
        }
        foreach (array_keys(self::PARTS) as $part) {
            $screens = array_merge($screens, self::partScreens($part, $content));
        }
        return $screens;
    }

    private static function partScreens(int $part, array $content): array
    {
        $p = $content['parts'][$part] ?? null;
        if (! $p) return [];
        $title = 'Listening Part ' . $part . ': ' . self::PARTS[$part];

        if (isset(self::PART_DIVISIONS[$part])) {
            $out = [];
            foreach (['s1', 's2'] as $s) {
                if (self::filledHtml($p[$s]['text'] ?? '') || filled($p[$s]['audio'] ?? '') || filled($p[$s]['image'] ?? '')) {
                    $out[] = self::info($title, $p[$s]);
                }
            }
            $divisions = [];
            foreach ($p['divisions'] as $d) {
                $qs = array_values(array_filter($d['questions'] ?? [], fn ($q) => self::questionUsable($q)));
                if (! $qs && ! filled($d['audio'] ?? '')) continue;
                $divisions[] = [$d, $qs];
            }
            $total = array_sum(array_map(fn ($x) => count($x[1]), $divisions));
            $n = 0;
            if (self::isGroupPart($part)) {
                // Part 4: the news item, then all questions on one screen with one timer
                foreach ($divisions as [$d, $qs]) {
                    $out[] = ['type' => 'recording', 'title' => $title, 'text' => $d['text'] ?? '', 'audio' => self::url($d['audio'] ?? '')];
                    if ($qs) {
                        $out[] = [
                            'type' => 'group', 'title' => $title, 'part' => $part,
                            'seconds' => self::GROUP_PARTS[$part]['seconds'],
                            'intro' => $d['questions_intro'] ?? self::groupIntro($part),
                            'questions' => array_map(fn ($q, $k) => [
                                'number' => $n + $k + 1, 'statement' => $q['statement'] ?? '',
                                'options' => array_map(fn ($o) => $o['text'] ?? '', $q['options']),
                                'correct' => ($q['correct'] ?? '') === '' ? null : (int) $q['correct'],
                            ], $qs, array_keys($qs)),
                        ];
                        $n += count($qs);
                    }
                }
                return $out;
            }
            foreach ($divisions as $i => [$d, $qs]) {
                $out[] = ['type' => 'recording', 'title' => $title, 'text' => $d['text'] ?? '', 'audio' => self::url($d['audio'] ?? '')];
                foreach ($qs as $q) {
                    $out[] = self::question($title, $q, ++$n, $total, $part);
                }
                if ($i < count($divisions) - 1) {
                    $out[] = ['type' => 'pause', 'title' => $title, 'seconds' => 10];
                }
            }
            return $out;
        }

        return []; // Parts 3–6: added one by one.
    }

    private static function info(string $title, array $s): array
    {
        return ['type' => 'info', 'title' => $title, 'text' => $s['text'] ?? '',
                'audio' => self::url($s['audio'] ?? ''), 'image' => self::url($s['image'] ?? '')];
    }

    private static function question(string $title, array $q, int $n, int $total, int $part): array
    {
        return [
            'type' => 'question', 'title' => $title, 'part' => $part, 'number' => $n, 'count' => $total, 'seconds' => 30,
            'text' => self::filledHtml($q['text'] ?? '') ? $q['text'] : '',
            'statement' => $q['statement'] ?? '',
            'audio' => self::url($q['audio'] ?? ''), 'image' => self::url($q['image'] ?? ''),
            'options' => array_map(fn ($o) => ['text' => $o['text'] ?? '', 'image' => self::url($o['image'] ?? '')], $q['options']),
            'correct' => ($q['correct'] ?? '') === '' ? null : (int) $q['correct'],
        ];
    }

    private static function questionUsable(array $q): bool
    {
        return (filled($q['statement'] ?? '') || self::filledHtml($q['text'] ?? ''))
            && collect($q['options'] ?? [])->contains(fn ($o) => filled($o['text'] ?? '') || filled($o['image'] ?? ''));
    }

    private static function filledHtml(?string $html): bool
    {
        return filled(trim(strip_tags($html ?? '', '<img>')));
    }

    private static function url(?string $path): ?string
    {
        return filled($path) ? asset($path) : null;
    }
}

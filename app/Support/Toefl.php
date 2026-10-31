<?php

namespace App\Support;

/**
 * TOEFL iBT (2026 format) question data.
 *
 * Every section is saved as one JSON value:
 *   { minutes: {...timers}, items: [ ...ordered question blocks... ], instructions: [ {text, module, from, to} ] }
 *
 * Reading   items: words (missing letters, # = one missing letter) | mc (passage + multiple-choice questions)
 * Listening items: response | conversation | announcement | talk   (each holds multiple-choice questions)
 * Writing   items: build (Build a Sentence) | email | discussion (Write for an Academic Discussion)
 * Speaking  items: repeat (Listen and Repeat) | interview (Take an Interview)
 */
class Toefl
{
    /** How a Reading passage looks to the student. */
    public const PASSAGE_STYLES = ['plain', 'notice', 'post', 'email', 'picture'];

    public const SECTIONS = [
        'reading'   => ['label' => 'Reading',   'icon' => 'pen',        'modules' => true],
        'listening' => ['label' => 'Listening', 'icon' => 'headphones', 'modules' => true],
        'writing'   => ['label' => 'Writing',   'icon' => 'page',       'modules' => false],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic',        'modules' => false],
    ];

    /** What students see about each section on the website. */
    public const PUBLIC = [
        'reading'   => ['label' => 'Reading',   'icon' => 'pen',        'time' => '30 minutes', 'parts' => '2 modules · 35–48 questions', 'desc' => 'Complete the words, read everyday texts and read academic passages.'],
        'listening' => ['label' => 'Listening', 'icon' => 'headphones', 'time' => '30 minutes', 'parts' => '2 modules · 35–45 questions', 'desc' => 'Choose a response, then listen to conversations, announcements and academic talks.'],
        'writing'   => ['label' => 'Writing',   'icon' => 'page',       'time' => '23 minutes', 'parts' => '3 task types', 'desc' => 'Build a sentence, write an email and write for an academic discussion.'],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic',        'time' => '8 minutes',  'parts' => '2 task types · 11 questions', 'desc' => 'Listen and repeat, then take a short interview.'],
    ];

    /** Item types of each section. */
    public const TYPES = [
        'reading'   => ['words' => 'Complete the Words', 'mc' => 'Multiple choice'],
        'listening' => ['response' => 'Listen and Choose a Response', 'conversation' => 'Listen to a Conversation', 'announcement' => 'Listen to an Announcement', 'talk' => 'Listen to an Academic Talk'],
        'writing'   => ['build' => 'Build a Sentence', 'email' => 'Write an Email', 'discussion' => 'Write for an Academic Discussion'],
        'speaking'  => ['repeat' => 'Listen and Repeat', 'interview' => 'Take an Interview'],
    ];

    /** Default timers: reading / listening modules in minutes, writing tasks in minutes, speaking answers in seconds. */
    public const DEFAULT_MINUTES = [
        'reading'   => ['1' => 15, '2' => 15],
        'listening' => ['1' => 15, '2' => 14],
        'writing'   => ['build' => 6, 'email' => 7, 'discussion' => 10],
        'speaking'  => [],
    ];

    public const DEFAULT_SECONDS = ['response' => 20, 'conversation' => 30, 'announcement' => 30, 'talk' => 30, 'repeat' => 15, 'interview' => 45];

    public const RESPONSE_STATEMENT = 'Listen and choose the best response.';

    private const HTML_TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h3><h4><a><img><table><thead><tbody><tr><td><th><span><mark><blockquote>';

    /* ───────── reading: missing letters ───────── */

    /** The blanks of a "Complete the Words" paragraph. "ed####" → one blank of 4 letters after "ed". */
    public static function blanks(string $html): array
    {
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/([\p{L}\'’-]*)(#+)([\p{L}\'’-]*)/u', $text, $m, PREG_SET_ORDER);

        return array_map(fn ($x) => ['prefix' => $x[1], 'len' => strlen($x[2]), 'suffix' => $x[3]], $m);
    }

    /* ───────── building the saved content ───────── */

    private static function clean(mixed $v): string
    {
        return trim((string) $v);
    }

    /** Rich text from the editor: keep the formatting tags, drop scripts and event handlers. */
    private static function html(mixed $v): string
    {
        $s = strip_tags((string) $v, self::HTML_TAGS);
        $s = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $s);
        $s = preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '', $s);

        return trim($s);
    }

    /** Only paths of this website's own TOEFL uploads are kept. */
    private static function file(mixed $v): string
    {
        $v = trim((string) $v);

        return preg_match('#^uploads/toefl/\d+/[\w./-]+$#', $v) && ! str_contains($v, '..') ? $v : '';
    }

    private static function options(mixed $v): array
    {
        $o = array_values(array_map(fn ($x) => self::clean($x), array_filter((array) $v, 'is_scalar')));

        return array_slice($o, 0, 8);
    }

    private static function question(array $q, bool $seconds = false, bool $media = false): array
    {
        $opts = self::options($q['options'] ?? []);
        $correct = isset($q['correct']) && is_numeric($q['correct']) ? (int) $q['correct'] : -1;
        $out = [
            'statement' => self::clean($q['statement'] ?? ''),
            'options'   => $opts,
            'correct'   => $correct >= 0 && $correct < count($opts) ? $correct : -1,
            'graded'    => ! isset($q['graded']) || (bool) $q['graded'],
        ];
        if ($seconds) $out['seconds'] = max(0, min(600, (int) ($q['seconds'] ?? 0)));
        if ($media) {
            $out['audio'] = self::file($q['audio'] ?? '');
            $out['image'] = self::file($q['image'] ?? '');
            $out['image_mode'] = in_array($q['image_mode'] ?? '', ['upload', 'male', 'female'], true) ? $q['image_mode'] : 'upload';
        }

        return $out;
    }

    private static function item(string $section, array $in): ?array
    {
        $type = $in['type'] ?? '';
        if (! isset(self::TYPES[$section][$type])) return null;
        $out = ['id' => preg_replace('/[^\w-]/', '', (string) ($in['id'] ?? '')) ?: bin2hex(random_bytes(4)), 'type' => $type];
        if (self::SECTIONS[$section]['modules']) $out['module'] = in_array((int) ($in['module'] ?? 1), [1, 2], true) ? (int) $in['module'] : 1;

        switch ($type) {
            case 'words':
                $out += ['graded' => ! isset($in['graded']) || (bool) $in['graded'], 'title' => self::clean($in['title'] ?? ''), 'paragraph' => self::html($in['paragraph'] ?? '')];
                $n = count(self::blanks($out['paragraph']));
                $answers = array_values((array) ($in['answers'] ?? []));
                $out['answers'] = array_map(fn ($i) => self::clean($answers[$i] ?? ''), range(0, max(0, $n - 1)));
                if ($n === 0) $out['answers'] = [];
                break;
            case 'mc':
                $out += [
                    'kind' => ($in['kind'] ?? '') === 'academic' ? 'academic' : 'daily',
                    'title' => self::clean($in['title'] ?? ''),
                    'style' => in_array($in['style'] ?? '', self::PASSAGE_STYLES, true) ? $in['style'] : 'plain',
                    'subtitle' => self::clean($in['subtitle'] ?? ''),
                    'author' => self::clean($in['author'] ?? ''),
                    'to' => self::clean($in['to'] ?? ''),
                    'from' => self::clean($in['from'] ?? ''),
                    'date' => self::clean($in['date'] ?? ''),
                    'subject' => self::clean($in['subject'] ?? ''),
                    'color' => in_array($in['color'] ?? '', ['teal', 'brown'], true) ? $in['color'] : 'teal',
                    'passage' => self::html($in['passage'] ?? ''),
                    'image' => self::file($in['image'] ?? ''),
                    'questions' => array_map(fn ($q) => self::question((array) $q) + ['highlight' => self::clean($q['highlight'] ?? '')], array_values(array_filter((array) ($in['questions'] ?? []), 'is_array'))),
                ];
                break;
            case 'response':
                $out['questions'] = array_map(fn ($q) => self::question((array) $q, true, true), array_values(array_filter((array) ($in['questions'] ?? []), 'is_array')));
                foreach ($out['questions'] as &$q) $q['statement'] = $q['statement'] ?: self::RESPONSE_STATEMENT;
                unset($q);
                break;
            case 'conversation':
            case 'announcement':
            case 'talk':
                $out += [
                    'title' => self::clean($in['title'] ?? ''),
                    'audio' => self::file($in['audio'] ?? ''),
                    'image' => self::file($in['image'] ?? ''),
                    'image_mode' => in_array($in['image_mode'] ?? '', ['upload', 'male', 'female', 'pair'], true) ? $in['image_mode'] : 'upload',
                    'questions' => array_map(fn ($q) => self::question((array) $q, true), array_values(array_filter((array) ($in['questions'] ?? []), 'is_array'))),
                ];
                break;
            case 'build':
                $out += ['statement' => self::clean($in['statement'] ?? ''), 'answer' => self::clean($in['answer'] ?? ''), 'options' => self::clean($in['options'] ?? ''), 'correct' => self::clean($in['correct'] ?? '')];
                break;
            case 'email':
                $out += ['statement' => self::html($in['statement'] ?? ''), 'instructions' => self::html($in['instructions'] ?? ''), 'to' => self::clean($in['to'] ?? ''), 'subject' => self::clean($in['subject'] ?? '')];
                break;
            case 'discussion':
                foreach (['professor', 'student1', 'student2'] as $p) {
                    $out[$p . '_image_mode'] = in_array($in[$p . '_image_mode'] ?? '', ['upload', 'male', 'female'], true) ? $in[$p . '_image_mode'] : 'male';
                    $out[$p . '_image'] = self::file($in[$p . '_image'] ?? '');
                    $out[$p . '_post'] = self::html($in[$p . '_post'] ?? '');
                }
                $out['instructions'] = self::html($in['instructions'] ?? '');
                break;
            case 'repeat':
                $out += ['statement' => self::clean($in['statement'] ?? ''), 'audio' => self::file($in['audio'] ?? ''), 'image' => self::file($in['image'] ?? ''), 'seconds' => max(5, min(300, (int) ($in['seconds'] ?? self::DEFAULT_SECONDS['repeat'])))];
                break;
            case 'interview':
                $out += ['statement' => self::clean($in['statement'] ?? ''), 'audio' => self::file($in['audio'] ?? ''), 'image' => self::file($in['image'] ?? ''), 'image_mode' => in_array($in['image_mode'] ?? '', ['upload', 'male', 'female'], true) ? $in['image_mode'] : 'female', 'seconds' => max(5, min(600, (int) ($in['seconds'] ?? self::DEFAULT_SECONDS['interview'])))];
                break;
        }

        return $out;
    }

    /** Make clean content out of what the admin page sent. */
    public static function fromInput(string $section, array $in): array
    {
        $items = [];
        foreach ((array) ($in['items'] ?? []) as $it) {
            if (is_array($it) && ($x = self::item($section, $it))) $items[] = $x;
        }
        $minutes = [];
        foreach (self::DEFAULT_MINUTES[$section] as $k => $def) {
            $v = $in['minutes'][$k] ?? $def;
            $minutes[$k] = max(0, min(120, (int) $v));
        }
        $instructions = [];
        foreach ((array) ($in['instructions'] ?? []) as $b) {
            if (! is_array($b) || self::clean($b['text'] ?? '') === '') continue;
            $instructions[] = [
                'text' => self::html($b['text']),
                'module' => in_array((int) ($b['module'] ?? 1), [1, 2], true) ? (int) $b['module'] : 1,
                'from' => max(1, (int) ($b['from'] ?? 1)),
                'to' => max(1, (int) ($b['to'] ?? 1)),
            ];
        }

        return ['minutes' => $minutes, 'items' => $items, 'instructions' => $instructions];
    }

    /** Content for the admin page, with defaults filled in. */
    public static function forEditing(string $section, ?array $content): array
    {
        $content ??= [];

        return [
            'minutes' => ($content['minutes'] ?? []) + self::DEFAULT_MINUTES[$section],
            'items' => array_values($content['items'] ?? []),
            'instructions' => array_values($content['instructions'] ?? []),
        ];
    }

    /* ───────── counting ───────── */

    /** Questions of one item as a list of ['graded' => bool]. */
    public static function itemQuestions(array $item): array
    {
        return match ($item['type'] ?? '') {
            'words' => array_fill(0, count(self::blanks($item['paragraph'] ?? '')), ['graded' => $item['graded'] ?? true]),
            'mc', 'response', 'conversation', 'announcement', 'talk' => array_map(fn ($q) => ['graded' => $q['graded'] ?? true], $item['questions'] ?? []),
            'build', 'email', 'discussion', 'repeat', 'interview' => [['graded' => true]],
            default => [],
        };
    }

    /** [module => ['questions' => n, 'graded' => n]] (writing / speaking use module 0). */
    public static function counts(string $section, array $content): array
    {
        $out = [];
        foreach ($content['items'] ?? [] as $item) {
            $m = self::SECTIONS[$section]['modules'] ? (int) ($item['module'] ?? 1) : 0;
            foreach (self::itemQuestions($item) as $q) {
                $out[$m]['questions'] = ($out[$m]['questions'] ?? 0) + 1;
                $out[$m]['graded'] = ($out[$m]['graded'] ?? 0) + ($q['graded'] ? 1 : 0);
            }
        }
        ksort($out);

        return $out;
    }

    public static function hasQuestions(string $section, ?array $content): bool
    {
        return array_sum(array_column(self::counts($section, $content ?? []), 'questions')) > 0;
    }

    /** What is still missing in a section (shown after saving). Empty = ready. */
    public static function problems(string $section, array $content): array
    {
        $p = [];
        $n = 0;
        foreach ($content['items'] ?? [] as $item) {
            $n++;
            $label = (self::TYPES[$section][$item['type']] ?? 'Item') . " #{$n}";
            switch ($item['type']) {
                case 'words':
                    $b = self::blanks($item['paragraph'] ?? '');
                    if (! $b) $p[] = "$label has no # blanks.";
                    foreach ($b as $i => $x) if (($item['answers'][$i] ?? '') === '') $p[] = "$label: blank " . ($i + 1) . ' has no answer.';
                    break;
                case 'build':
                    $slots = substr_count($item['answer'] ?? '', '#');
                    $correct = array_filter(array_map('trim', explode('/', $item['correct'] ?? '')), 'strlen');
                    $opts = array_filter(array_map('trim', explode('/', $item['options'] ?? '')), 'strlen');
                    if ($slots === 0) $p[] = "$label: the answer statement has no # blank.";
                    elseif (count($correct) !== $slots) $p[] = "$label: $slots blanks but " . count($correct) . ' words in the correct order.';
                    if (array_diff($correct, $opts)) $p[] = "$label: a word in the correct order is missing from the options.";
                    break;
                case 'email':
                case 'discussion':
                case 'repeat':
                case 'interview':
                    break;
                default:
                    foreach ($item['questions'] ?? [] as $i => $q) {
                        if (($q['statement'] ?? '') === '' || count($q['options'] ?? []) < 2 || ($q['correct'] ?? -1) < 0) $p[] = "$label: question " . ($i + 1) . ' needs a statement, options and a correct answer.';
                    }
                    if (in_array($item['type'], ['conversation', 'announcement', 'talk'], true) && ($item['audio'] ?? '') === '') $p[] = "$label has no recording.";
            }
        }

        return array_slice($p, 0, 12);
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * IELTS (General Training) Reading – content, question numbering, rendering and marking.
 *
 * Saved in ielts_test_modules.content (module = "reading"):
 *   passages.{1,2,3}   the three reading passages / sections (HTML). A paragraph that starts with
 *                      [A], [B] … gets that letter as its label (used by Headings and Matching).
 *   groups[]           question groups, in the order they were added:
 *     { id, type, passage (1–3), instructions (HTML), …type fields }
 *
 * Question numbers are never saved – they are worked out from the order:
 * passage 1's groups first, then passage 2's, then passage 3's.
 *
 * Blanks: in fill-in-the-blank questions every "#" in the text becomes an answer box.
 */
class IeltsReading
{
    public const MINUTES = 60;

    /** The numbered parts of the test (Reading: 3 passages; Listening: 4 parts). */
    public const PARTS = [1, 2, 3];

    /** How many options a multiple-choice question has (Reading 4, Listening 3). */
    public const MCQ_OPTIONS = 4;

    /** Types shown in a two-column (split) layout – Listening only, see IeltsListening. */
    public const SPLIT_TYPES = [];

    /** Question types, in the order of the buttons on the admin page. */
    public const TYPES = [
        'fitb'       => ['button' => 'Add FITB',               'label' => 'Fill in the blanks'],
        'fitb_flow'  => ['button' => 'Add FITB – Flow Chart',  'label' => 'Fill in the blanks – flow chart'],
        'fitb_table' => ['button' => 'Add FITB – Table',       'label' => 'Fill in the blanks – table'],
        'fitb_para'  => ['button' => 'Add FITB – Paragraph',   'label' => 'Fill in the blanks – paragraph'],
        'fitb_label' => ['button' => 'Add FITB – Label',       'label' => 'Fill in the blanks – label a diagram'],
        'fitb_syn'   => ['button' => 'Add FITB – Synonyms',    'label' => 'Fill in the blanks – synonyms (choose from a list of words)'],
        'tfng'       => ['button' => 'Add TRUE / FALSE',       'label' => 'True / False / Not Given'],
        'mcq'        => ['button' => 'Add MCQ',                'label' => 'Multiple choice (A–D)'],
        'headings'   => ['button' => 'Add Headings',           'label' => 'Matching headings'],
        'matching'   => ['button' => 'Add Matching Statement', 'label' => 'Matching statements'],
        'matching_items' => ['button' => 'Add Matching Statement – Items', 'label' => 'Matching statements – items'],
        'matching_names' => ['button' => 'Add Matching Statement – Names List', 'label' => 'Matching statements – list of names'],
        'mcq5'       => ['button' => 'Add MCQ – 5 Options',    'label' => 'Multiple choice – choose TWO (A–E)'],
    ];

    public const TFNG_SETS = [
        'tf' => ['TRUE', 'FALSE', 'NOT GIVEN'],
        'yn' => ['YES', 'NO', 'NOT GIVEN'],
    ];

    public const BLANK_TYPES = ['fitb', 'fitb_flow', 'fitb_table', 'fitb_para'];

    /* ═════════════════════════ saved content ═════════════════════════ */

    public static function normalize(?array $saved): array
    {
        $saved ??= [];
        $out = ['passages' => [], 'groups' => [], 'audio' => (string) ($saved['audio'] ?? '')];
        foreach (static::PARTS as $p) {
            $out['passages'][$p] = (string) ($saved['passages'][$p] ?? $saved['passages'][(string) $p] ?? '');
        }
        foreach ((array) ($saved['groups'] ?? []) as $g) {
            if (is_array($g) && isset(static::TYPES[$g['type'] ?? ''])) $out['groups'][] = static::cleanGroup($g);
        }

        return $out;
    }

    /** Clean one question group (from the admin form or from the database). */
    public static function cleanGroup(array $g): array
    {
        $type = (string) $g['type'];
        $letter = fn ($v, $max = 26) => is_string($v) && preg_match('/^[A-Z]$/', $v) && ord($v) - 64 <= $max ? $v : '';
        $out = [
            'id'           => preg_match('/^g[\w]{4,40}$/', (string) ($g['id'] ?? '')) ? $g['id'] : 'g' . Str::lower(Str::random(10)),
            'type'         => $type,
            'passage'      => in_array((int) ($g['passage'] ?? 1), static::PARTS, true) ? (int) $g['passage'] : 1,
            'instructions' => (string) ($g['instructions'] ?? ''),
        ];
        $items = array_values(array_filter((array) ($g['items'] ?? []), 'is_array'));
        $answers = fn ($a) => array_values(array_map(fn ($x) => trim((string) $x), (array) $a));

        switch ($type) {
            case 'fitb':
            case 'fitb_para':
            case 'fitb_flow':
                $out['items'] = array_map(fn ($i) => array_filter([
                    'text'    => (string) ($i['text'] ?? ''),
                    'answers' => array_slice($answers($i['answers'] ?? []), 0, static::countBlanks((string) ($i['text'] ?? ''))),
                    'arrow'   => $type === 'fitb_flow' ? ! empty($i['arrow']) : null,
                ], fn ($v) => $v !== null), $items);
                break;
            case 'fitb_syn':
                // One paragraph with # blanks, a list of lettered words (A, B, C…) and, for each blank, the letter of its answer.
                // The list and the answers are separate: the list may hold more words than there are blanks.
                $out['text'] = (string) ($g['text'] ?? '');
                $out['options'] = array_slice(array_values(array_map('strval', (array) ($g['options'] ?? []))), 0, 26);
                $out['answers'] = array_map(fn ($a) => $letter($a, count($out['options'])), array_slice(array_values((array) ($g['answers'] ?? [])), 0, static::countBlanks($out['text'])));
                break;
            case 'fitb_label':
                // A picture with numbered points (x, y in % of the picture); the student types the answer for each point.
                $out['image'] = static::cleanUpload((string) ($g['image'] ?? ''));
                $out['items'] = array_map(fn ($i) => [
                    'x'      => round(max(0, min(100, (float) ($i['x'] ?? 0))), 2),
                    'y'      => round(max(0, min(100, (float) ($i['y'] ?? 0))), 2),
                    'answer' => trim((string) ($i['answer'] ?? '')),
                ], array_slice($items, 0, 40));
                break;
            case 'fitb_table':
                $out['table'] = (string) ($g['table'] ?? '');
                $out['answers'] = array_slice($answers($g['answers'] ?? []), 0, static::countBlanks($out['table']));
                break;
            case 'tfng':
                $out['set'] = isset(static::TFNG_SETS[$g['set'] ?? '']) ? $g['set'] : 'tf';
                $choices = static::TFNG_SETS[$out['set']];
                $out['items'] = array_map(fn ($i) => [
                    'text'   => (string) ($i['text'] ?? ''),
                    'answer' => in_array($i['answer'] ?? '', $choices, true) ? $i['answer'] : '',
                ], $items);
                break;
            case 'mcq':
            case 'mcq5':
                $n = $type === 'mcq' ? static::MCQ_OPTIONS : 5;
                $out['items'] = array_map(function ($i) use ($n, $letter, $type) {
                    $opts = array_slice(array_pad(array_map('strval', (array) ($i['options'] ?? [])), $n, ''), 0, $n);
                    $row = ['text' => (string) ($i['text'] ?? ''), 'options' => $opts];
                    if ($type === 'mcq') {
                        $row['answer'] = $letter($i['answer'] ?? '', $n);
                    } else {
                        $row['answers'] = array_values(array_unique(array_slice(array_filter(array_map(fn ($a) => $letter($a, $n), (array) ($i['answers'] ?? []))), 0, 2)));
                        sort($row['answers']);
                    }

                    return $row;
                }, $items);
                break;
            case 'headings':
                $out['items'] = array_map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'answer' => $letter($i['answer'] ?? '')], $items);
                break;
            case 'matching_names':
                // Statements (one mark each) + a list of lettered names in one box. The student types the letter of the right name.
                $out['ohead'] = mb_substr(trim(strip_tags((string) ($g['ohead'] ?? ''))), 0, 150);
                $out['options'] = array_slice(array_values(array_map('strval', (array) ($g['options'] ?? []))), 0, 26);
                $out['items'] = array_map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'answer' => $letter($i['answer'] ?? '', count($out['options']))], $items);
                break;
            case 'matching_items':
            case 'drag':
                // First cell text (matching_items only), the options (columns / draggable list) and the statements, the items (columns) and the statements (rows).
                $out['corner'] = (string) ($g['corner'] ?? '');
                $out['qhead'] = mb_substr(trim(strip_tags((string) ($g['qhead'] ?? ''))), 0, 150);
                $out['ohead'] = mb_substr(trim(strip_tags((string) ($g['ohead'] ?? ''))), 0, 150);
                $out['options'] = array_slice(array_values(array_map('strval', (array) ($g['options'] ?? []))), 0, 26);
                $out['items'] = array_map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'answer' => $letter($i['answer'] ?? '', count($out['options']))], $items);
                break;
            case 'map':
                // Map labelling (Listening): an image + statements, answered by clicking a letter in a table.
                $out['image'] = static::cleanUpload((string) ($g['image'] ?? ''));
                $out['letters'] = max(2, min(12, (int) ($g['letters'] ?? 5)));
                $out['items'] = array_map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'answer' => $letter($i['answer'] ?? '', $out['letters'])], $items);
                break;
            case 'mapdrop':
                // Map labelling, drag-and-drop version: an image with numbered points (x, y in % of the image); each point's answer is a draggable option.
                $out['image'] = static::cleanUpload((string) ($g['image'] ?? ''));
                $out['items'] = array_map(fn ($i) => [
                    'x'    => round(max(0, min(100, (float) ($i['x'] ?? 0))), 2),
                    'y'    => round(max(0, min(100, (float) ($i['y'] ?? 0))), 2),
                    'text' => (string) ($i['text'] ?? ''),
                ], array_slice($items, 0, 26));
                break;
            case 'matching':
                $out['letters'] = max(2, min(12, (int) ($g['letters'] ?? 5)));
                $out['items'] = array_map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'answer' => $letter($i['answer'] ?? '', $out['letters'])], $items);
                break;
        }

        return $out;
    }

    /** Drag-and-drop matching (Listening): [the numbered questions with an empty box each, the draggable options]. */
    private static function renderDrag(array $g): array
    {
        $q = $g['start'];
        $questions = ($g['qhead'] ?? '') !== '' ? '<p class="rd-dhead">' . e($g['qhead']) . '</p>' : '';
        foreach ($g['items'] as $item) {
            $questions .= '<div class="rd-dq" data-qbox="' . $q . '"><b class="rd-num">' . $q . '</b><div class="rd-dq__text">' . static::inline($item['text']) . '</div>'
                . '<div class="rd-drop" data-drop-q="' . $q . '" data-hg="' . $g['start'] . '"><span class="rd-drop__hint">' . $q . '</span></div></div>';
            $q++;
        }
        $options = (($g['ohead'] ?? '') !== '' ? '<p class="rd-dhead">' . e($g['ohead']) . '</p>' : '') . '<div class="rd-headings" data-headings data-hg="' . $g['start'] . '">';
        foreach ($g['options'] as $i => $text) {
            $options .= '<div class="rd-heading" draggable="true" data-heading="' . $i . '" data-hg="' . $g['start'] . '">'
                . strip_tags(static::inline($text), '<em><i><strong><b>') . '</div>';
        }

        return [$questions, $options . '</div>'];
    }

    /** Map labelling, drag-and-drop version: [the picture with an empty drop box on each point, the draggable answers in a mixed-up order]. */
    private static function renderMapDrop(array $g): array
    {
        $q = $g['start'];
        $pins = '';
        foreach ($g['items'] as $item) {
            $pins .= '<div class="rd-drop rd-pin" style="left:' . $item['x'] . '%;top:' . $item['y'] . '%" data-qbox="' . $q . '" data-drop-q="' . $q . '" data-hg="' . $g['start'] . '"><span class="rd-drop__hint">' . $q . '</span></div>';
            $q++;
        }
        $map = '<div class="rd-mapdrop">' . ($g['image'] !== '' ? '<img class="rd-map" src="' . e(asset($g['image'])) . '" alt="Map">' : '') . $pins . '</div>';

        // The answers are listed in a fixed, mixed-up order (so the list does not give the answers away).
        $order = array_keys($g['items']);
        mt_srand(crc32($g['id']));
        for ($i = count($order) - 1; $i > 0; $i--) { $j = mt_rand(0, $i); [$order[$i], $order[$j]] = [$order[$j], $order[$i]]; }
        mt_srand();
        $options = '<div class="rd-headings" data-headings data-hg="' . $g['start'] . '">';
        foreach ($order as $i) {
            $options .= '<div class="rd-heading" draggable="true" data-heading="' . $i . '" data-hg="' . $g['start'] . '">'
                . strip_tags(static::inline($g['items'][$i]['text']), '<em><i><strong><b>') . '</div>';
        }

        return [$map, $options . '</div>'];
    }

    /** Only keep an uploaded file path that points to this website's own Listening uploads. */
    public static function cleanUpload(string $path): string
    {
        return preg_match('#^uploads/ielts/\d+/(listening|reading)/[\w.\-]+$#', $path) ? $path : '';
    }

    /** A whole question group as the student (and the admin preview) sees it: heading, instructions, then the questions. */
    public static function groupHtml(array $g): string
    {
        $head = $g['count'] ? '<p class="rd-group__head">' . static::questionRange($g['start'], $g['count']) . '</p>' : '';
        $inst = '<div class="rd-group__inst">' . $g['instructions'] . '</div>';
        $cls = 'rd-group rd-group--' . $g['type'];
        if ($g['type'] === 'mapdrop') {
            [$map, $options] = static::renderMapDrop($g);

            return '<div class="' . $cls . ' rd-group--split"><div class="rd-split2"><div class="rd-split2__left">' . $head . $inst . $map
                . '</div><div class="rd-split2__right">' . $options . '</div></div></div>';
        }
        if ($g['type'] === 'drag') {
            [$questions, $options] = static::renderDrag($g);

            return '<div class="' . $cls . ' rd-group--split"><div class="rd-split2"><div class="rd-split2__left">' . $head . $inst . $questions
                . '</div><div class="rd-split2__right">' . $options . '</div></div></div>';
        }
        if (! in_array($g['type'], static::SPLIT_TYPES, true)) {
            return '<div class="' . $cls . '">' . $head . $inst . static::render($g) . '</div>';
        }
        // Two columns: heading, instructions and image on the left, the answer table on the right.
        $img = ! empty($g['image']) ? '<img class="rd-map" src="' . e(asset($g['image'])) . '" alt="">' : '';

        return '<div class="' . $cls . ' rd-group--split"><div class="rd-split2"><div class="rd-split2__left">' . $head . $inst . $img
            . '</div><div class="rd-split2__right">' . static::render($g) . '</div></div></div>';
    }

    /* ═════════════════════════ blanks (#) ═════════════════════════ */

    /** Every "#" in the TEXT of some HTML (not inside tags or entities like &#39;). */
    public static function countBlanks(string $html): int
    {
        $n = 0;
        static::replaceBlanks($html, function () use (&$n) { $n++; return '#'; });

        return $n;
    }

    /** Replace each blank with whatever $make($index) returns (index starts at 0). */
    public static function replaceBlanks(string $html, callable $make): string
    {
        $i = 0;
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $k => $part) {
            if ($part === '' || $part[0] === '<') continue;
            $parts[$k] = preg_replace_callback('/&#?\w+;|#/', function ($m) use (&$i, $make) {
                return $m[0] === '#' ? $make($i++) : $m[0];
            }, $part);
        }

        return implode('', $parts);
    }

    /* ═════════════════════════ question numbering ═════════════════════════ */

    /**
     * How many question numbers a group uses.
     * Headings: one question per lettered paragraph ([A], [B] …) in its passage – the boxes are the questions;
     * the headings are only the choices, so there can be more headings than paragraphs.
     */
    public static function questionCount(array $g, ?array $content = null): int
    {
        return match ($g['type']) {
            'fitb_para'  => array_sum(array_map(fn ($i) => static::countBlanks($i['text'] ?? ''), $g['items'] ?? [])),
            'fitb', 'fitb_flow' => count(array_filter($g['items'] ?? [], fn ($i) => static::countBlanks($i['text'] ?? '') > 0)),
            'fitb_table' => static::countBlanks($g['table'] ?? ''),
            'fitb_syn'   => static::countBlanks($g['text'] ?? ''),
            'mcq5'       => 2 * count($g['items'] ?? []),
            'headings'   => $content !== null
                ? count(static::letters($content['passages'][$g['passage']] ?? ''))
                : count(array_filter($g['items'] ?? [], fn ($i) => ($i['answer'] ?? '') !== '')),
            default      => count($g['items'] ?? []),
        };
    }

    /** The groups in test order (passage 1, 2, 3), each with its first question number ("start") and "count". */
    public static function ordered(array $content): array
    {
        $out = [];
        $next = 1;
        foreach (static::PARTS as $p) {
            foreach ($content['groups'] as $index => $g) {
                if ($g['passage'] !== $p) continue;
                $count = static::questionCount($g, $content);
                $out[] = $g + ['start' => $next, 'count' => $count, 'index' => $index];
                $next += $count;
            }
        }

        return $out;
    }

    public static function total(array $content): int
    {
        return array_sum(array_column(static::ordered($content), 'count'));
    }

    /** First and last question numbers of each passage: [1 => [1, 13], …] (null when it has no questions). */
    public static function ranges(array $content): array
    {
        $r = array_fill_keys(static::PARTS, null);
        foreach (static::ordered($content) as $g) {
            if (! $g['count']) continue;
            $last = $g['start'] + $g['count'] - 1;
            $r[$g['passage']] = $r[$g['passage']] ? [$r[$g['passage']][0], $last] : [$g['start'], $last];
        }

        return $r;
    }

    public static function isReady(?array $saved): bool
    {
        $c = static::normalize($saved);

        return IeltsSpeaking::filled($c['passages'][1]) && static::total($c) > 0;
    }

    /* ═════════════════════════ passages ═════════════════════════ */

    /** Paragraph letters marked with [A], [B] … in a passage. */
    public static function letters(string $html): array
    {
        preg_match_all('#<p\b[^>]*>(?:\s|&nbsp;|<(?:strong|b|em|span)\b[^>]*>)*\[([A-Z])\]#u', $html, $m);

        return array_values(array_unique($m[1]));
    }

    /**
     * The Headings group of a passage, if it has one: [start number, [letter => question number]].
     */
    public static function headingBoxes(array $content, int $p): ?array
    {
        foreach (static::ordered($content) as $g) {
            if ($g['passage'] !== $p || $g['type'] !== 'headings') continue;
            $map = [];
            foreach (static::letters($content['passages'][$p]) as $i => $l) $map[$l] = $g['start'] + $i;

            return ['start' => $g['start'], 'boxes' => $map];
        }

        return null;
    }

    /**
     * A passage for the student. [A] markers become bold letters – except when the passage has Headings
     * questions: then the letters are hidden and a numbered box (the question) goes above each lettered paragraph.
     */
    public static function passageHtml(string $html, ?array $headings = null): string
    {
        return preg_replace_callback(
            '#<p\b([^>]*)>((?:\s|&nbsp;|<(?:strong|b|em|span)\b[^>]*>)*)\[([A-Z])\](?:\s|&nbsp;)*#u',
            function ($m) use ($headings) {
                if ($headings && isset($headings['boxes'][$m[3]])) {
                    $q = $headings['boxes'][$m[3]];

                    return '<div class="rd-drop" data-drop-q="' . $q . '" data-hg="' . $headings['start'] . '" data-qbox="' . $q . '">'
                        . '<span class="rd-drop__hint">' . $q . '</span></div><p' . $m[1] . ' data-para="' . $m[3] . '">' . $m[2];
                }

                return '<p' . $m[1] . ' data-para="' . $m[3] . '">' . $m[2] . '<b class="rd-plabel">' . $m[3] . '</b> ';
            },
            $html
        );
    }

    /* ═════════════════════════ rendering a group (student page + admin preview) ═════════════════════════ */

    private static function blank(int $q, int $b, bool $letter = false): string
    {
        return '<input type="text" class="rd-blank' . ($letter ? ' rd-blank--letter' : '') . '"' . ($letter ? ' maxlength="1" inputmode="text"' : '') . ' data-q="' . $q . '" data-b="' . $b . '" placeholder="' . $q . '" aria-label="Question ' . $q
            . '" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">';
    }

    /** Strip one outer <p> so sentences can sit side by side in one paragraph. */
    private static function inline(string $html): string
    {
        $html = trim($html);

        return preg_match('#^<p\b[^>]*>(.*)</p>$#s', $html, $m) && ! str_contains($m[1], '<p') ? $m[1] : $html;
    }

    public static function questionRange(int $start, int $count): string
    {
        return $count > 1 ? 'Questions ' . $start . '–' . ($start + $count - 1) : 'Question ' . $start;
    }

    public static function render(array $g): string
    {
        $q = $g['start'];
        $e = fn ($s) => e($s);
        $html = '';
        switch ($g['type']) {
            case 'fitb':
            case 'fitb_flow':
            case 'fitb_para':
                $rows = [];
                foreach ($g['items'] as $item) {
                    $text = $item['text'] ?? '';
                    $has = static::countBlanks($text) > 0;
                    $num = $q;
                    $n = static::countBlanks($text);
                    if ($g['type'] === 'fitb_para') {
                        // Paragraph: every blank is its own question.
                        $out = $has ? static::replaceBlanks($text, fn ($b) => static::blank($num + $b, 0)) : $text;
                        $q += $n;
                    } else {
                        $out = $has ? static::replaceBlanks($text, fn ($b) => static::blank($num, $b)) : $text;
                        if ($has) $q++;
                    }
                    $rows[] = ['html' => $out, 'q' => $has ? $num : null, 'arrow' => ! empty($item['arrow'])];
                }
                if ($g['type'] === 'fitb_para') {
                    $html = '<p class="rd-para">' . implode(' ', array_map(fn ($r) => static::inline($r['html']), $rows)) . '</p>';
                } elseif ($g['type'] === 'fitb_flow') {
                    $html = '<div class="rd-flow">';
                    foreach ($rows as $r) {
                        $html .= '<div class="rd-flow__box"' . ($r['q'] ? ' data-qbox="' . $r['q'] . '"' : '') . '>' . $r['html'] . '</div>';
                        if ($r['arrow']) $html .= '<div class="rd-flow__arrow" aria-hidden="true">↓</div>';
                    }
                    $html .= '</div>';
                } else {
                    $html = '<div class="rd-fitb">';
                    foreach ($rows as $r) $html .= '<div class="rd-fitb__item"' . ($r['q'] ? ' data-qbox="' . $r['q'] . '"' : '') . '>' . $r['html'] . '</div>';
                    $html .= '</div>';
                }
                break;

            case 'fitb_syn':
                // The paragraph (every blank is its own question; the student types a letter) and the box of lettered words.
                $html = '<div class="rd-para">' . static::replaceBlanks(static::inline($g['text'] ?? ''), fn ($b) => static::blank($q + $b, 0, true)) . '</div>'
                    . '<div class="rd-wordbox">';
                foreach ($g['options'] ?? [] as $k => $opt) {
                    $html .= '<div class="rd-wordbox__item"><b>' . chr(65 + $k) . '</b><span>' . strip_tags(static::inline($opt), '<em><i><strong><b><u>') . '</span></div>';
                }
                $html .= '</div>';
                break;

            case 'fitb_label':
                // The picture with a numbered box and an answer box on each point.
                $pins = '';
                foreach ($g['items'] as $item) {
                    $pins .= '<div class="rd-lpin' . ($item['x'] > 62 ? ' rd-lpin--left' : '') . '" style="left:' . $item['x'] . '%;top:' . $item['y'] . '%" data-qbox="' . $q . '"><b class="rd-num">' . $q . '</b>' . static::blank($q, 0) . '</div>';
                    $q++;
                }
                $html = '<div class="rd-label">' . (($g['image'] ?? '') !== '' ? '<img class="rd-map" src="' . e(asset($g['image'])) . '" alt="Diagram">' : '') . $pins . '</div>';
                break;

            case 'matching_names':
                // One statement after the other, each with a small box for the letter; then the list of names in one box.
                foreach ($g['items'] as $item) {
                    $html .= '<div class="rd-ms" data-qbox="' . $q . '"><b class="rd-num">' . $q . '</b><div class="rd-ms__text">' . static::inline($item['text']) . '</div>'
                        . static::blank($q, 0, true) . '</div>';
                    $q++;
                }
                $html .= '<div class="rd-namebox">' . (($g['ohead'] ?? '') !== '' ? '<p class="rd-namebox__head">' . e($g['ohead']) . '</p>' : '');
                foreach ($g['options'] ?? [] as $k => $opt) {
                    $html .= '<div class="rd-namebox__item"><b>' . chr(65 + $k) . '</b><span>' . strip_tags(static::inline($opt), '<em><i><strong><b><u>') . '</span></div>';
                }
                $html .= '</div>';
                break;

            case 'fitb_table':
                $html = '<div class="rd-table">' . static::replaceBlanks($g['table'] ?? '', fn ($b) => static::blank($q + $b, 0)) . '</div>';
                break;

            case 'tfng':
                $choices = static::TFNG_SETS[$g['set'] ?? 'tf'];
                foreach ($g['items'] as $item) {
                    $html .= '<div class="rd-tf" data-qbox="' . $q . '"><button type="button" class="rd-tf__q" data-toggle aria-expanded="false"><b class="rd-num">' . $q . '</b>'
                        . '<span class="rd-tf__text">' . static::inline($item['text']) . '</span><span class="rd-tf__chosen" data-chosen="' . $q . '"></span></button>'
                        . '<div class="rd-tf__opts" hidden>';
                    foreach ($choices as $c) {
                        $html .= '<label class="rd-opt"><input type="radio" name="q' . $q . '" value="' . $e($c) . '" data-q="' . $q . '"> ' . $e($c) . '</label>';
                    }
                    $html .= '</div></div>';
                    $q++;
                }
                break;

            case 'mcq':
                foreach ($g['items'] as $item) {
                    $html .= '<div class="rd-mcq" data-qbox="' . $q . '"><div class="rd-mcq__q"><b class="rd-num">' . $q . '</b><div>' . static::inline($item['text']) . '</div></div><div class="rd-mcq__opts">';
                    foreach ($item['options'] as $k => $opt) {
                        $l = chr(65 + $k);
                        $html .= '<label class="rd-opt"><input type="radio" name="q' . $q . '" value="' . $l . '" data-q="' . $q . '"> <b>' . $l . '</b> <span>' . static::inline($opt) . '</span></label>';
                    }
                    $html .= '</div></div>';
                    $q++;
                }
                break;

            case 'mcq5':
                foreach ($g['items'] as $item) {
                    $html .= '<div class="rd-mcq rd-mcq--two" data-qbox="' . $q . '"><div class="rd-mcq__q"><b class="rd-num">' . $q . '–' . ($q + 1) . '</b><div>'
                        . static::inline($item['text']) . '</div></div><div class="rd-mcq__opts">';
                    foreach ($item['options'] as $k => $opt) {
                        $l = chr(65 + $k);
                        $html .= '<label class="rd-opt"><input type="checkbox" value="' . $l . '" data-q="' . $q . '" data-two> <b>' . $l . '</b> <span>' . static::inline($opt) . '</span></label>';
                    }
                    $html .= '</div></div>';
                    $q += 2;
                }
                break;

            case 'headings':
                // The headings are the choices (no numbers); the questions are the numbered boxes in the passage.
                $html = '<p class="rd-list-title">List of Headings</p><div class="rd-headings" data-headings data-hg="' . $g['start'] . '">';
                foreach ($g['items'] as $i => $item) {
                    $html .= '<div class="rd-heading" draggable="true" data-heading="' . $i . '" data-hg="' . $g['start'] . '">'
                        . strip_tags(static::inline($item['text']), '<em><i><strong><b>') . '</div>';
                }
                $html .= '</div>';
                break;

            case 'drag':
                $html = implode('', static::renderDrag($g));
                break;

            case 'matching':
            case 'matching_items':
            case 'map':
                $items2 = $g['type'] === 'matching_items';
                $n = $items2 ? count($g['options']) : ($g['letters'] ?? 5);
                $corner = $items2 ? static::inline($g['corner'] ?? '') : ($g['type'] === 'map' ? '' : 'Statements');
                $html = '<table class="rd-match' . ($items2 ? ' rd-match--items' : '') . ($g['type'] === 'map' ? ' rd-match--map' : '') . '"><thead><tr><th>' . $corner . '</th>';
                for ($k = 0; $k < $n; $k++) $html .= '<th>' . ($items2 ? static::inline($g['options'][$k]) : chr(65 + $k)) . '</th>';
                $html .= '</tr></thead><tbody>';
                foreach ($g['items'] as $item) {
                    $html .= '<tr data-qbox="' . $q . '"><td class="rd-match__q"><b class="rd-num">' . $q . '</b> ' . static::inline($item['text']) . '</td>';
                    for ($k = 0; $k < $n; $k++) {
                        $l = chr(65 + $k);
                        $html .= '<td class="rd-match__cell" data-cell data-q="' . $q . '" data-value="' . $l . '" role="button" tabindex="0" aria-label="Question ' . $q . ': ' . $l . '"></td>';
                    }
                    $html .= '</tr>';
                    $q++;
                }
                $html .= '</tbody></table>';
                break;
        }

        return $html;
    }

    /* ═════════════════════════ marking ═════════════════════════ */

    /** Every question number → what counts as correct (and how to show it). */
    public static function answerKey(array $content): array
    {
        $key = [];
        foreach (static::ordered($content) as $g) {
            $q = $g['start'];
            switch ($g['type']) {
                case 'fitb_para':
                    foreach ($g['items'] as $item) {
                        $n = static::countBlanks($item['text'] ?? '');
                        foreach (array_pad(array_slice($item['answers'] ?? [], 0, $n), $n, '') as $a) {
                            $key[$q++] = ['kind' => 'blanks', 'answers' => [$a]];
                        }
                    }
                    break;
                case 'fitb':
                case 'fitb_flow':
                    foreach ($g['items'] as $item) {
                        if (static::countBlanks($item['text'] ?? '') === 0) continue;
                        $key[$q++] = ['kind' => 'blanks', 'answers' => $item['answers'] ?? []];
                    }
                    break;
                case 'fitb_table':
                    foreach (array_pad($g['answers'] ?? [], static::countBlanks($g['table'] ?? ''), '') as $a) {
                        $key[$q++] = ['kind' => 'blanks', 'answers' => [$a]];
                    }
                    break;
                case 'fitb_label':
                    foreach ($g['items'] as $item) $key[$q++] = ['kind' => 'blanks', 'answers' => [$item['answer'] ?? '']];
                    break;
                case 'matching_names':
                    $words = array_map(fn ($o) => trim(html_entity_decode(strip_tags($o))), $g['options'] ?? []);
                    foreach ($g['items'] as $item) $key[$q++] = ['kind' => 'blanks', 'answers' => [$item['answer'] ?? ''], 'words' => $words];
                    break;
                case 'fitb_syn':
                    // The answer is the letter of a word in the list.
                    $words = array_map(fn ($o) => trim(html_entity_decode(strip_tags($o))), $g['options'] ?? []);
                    foreach (array_pad($g['answers'] ?? [], static::countBlanks($g['text'] ?? ''), '') as $a) {
                        $key[$q++] = ['kind' => 'blanks', 'answers' => [$a], 'words' => $words];
                    }
                    break;
                case 'headings':
                    // Box above paragraph A, B … → the heading the admin matched to that paragraph.
                    $texts = array_map(fn ($i) => trim(html_entity_decode(strip_tags($i['text'] ?? ''))), $g['items']);
                    foreach (static::letters($content['passages'][$g['passage']]) as $l) {
                        $idx = array_search($l, array_column($g['items'], 'answer'), true);
                        $key[$q++] = ['kind' => 'heading', 'answer' => $idx === false ? '' : (string) $idx, 'texts' => $texts, 'para' => $l];
                    }
                    break;
                case 'drag':
                    // Each question → the position of its correct option in the list.
                    $texts = array_map(fn ($o) => trim(html_entity_decode(strip_tags($o))), $g['options']);
                    foreach ($g['items'] as $item) {
                        $a = $item['answer'] ?? '';
                        $key[$q++] = ['kind' => 'heading', 'answer' => $a === '' ? '' : (string) (ord($a) - 65), 'texts' => $texts, 'para' => ''];
                    }
                    break;
                case 'mapdrop':
                    // Point k's correct answer is the k-th option (the options are the answers of the points).
                    $texts = array_map(fn ($i) => trim(html_entity_decode(strip_tags($i['text'] ?? ''))), $g['items']);
                    foreach ($g['items'] as $k => $item) $key[$q++] = ['kind' => 'heading', 'answer' => (string) $k, 'texts' => $texts, 'para' => ''];
                    break;
                case 'tfng':
                case 'mcq':
                case 'matching':
                case 'matching_items':
                case 'map':
                    foreach ($g['items'] as $item) $key[$q++] = ['kind' => 'choice', 'answer' => $item['answer'] ?? ''];
                    break;
                case 'mcq5':
                    foreach ($g['items'] as $item) {
                        $key[$q] = ['kind' => 'two', 'answers' => $item['answers'] ?? [], 'part' => 1];
                        $key[$q + 1] = ['kind' => 'two', 'answers' => $item['answers'] ?? [], 'part' => 2, 'first' => $q];
                        $q += 2;
                    }
                    break;
            }
        }

        return $key;
    }

    /** "colour / color" → each accepted spelling, compared without case, extra spaces or end punctuation. */
    private static function same(string $given, string $accepted): bool
    {
        $norm = fn ($s) => trim(preg_replace('/\s+/u', ' ', mb_strtolower(trim(html_entity_decode(strip_tags($s)), " \t\n\r\0\x0B.,;:!?\"'"))));
        $g = $norm($given);
        if ($g === '') return false;
        foreach (explode('/', $accepted) as $alt) {
            if ($norm($alt) !== '' && $norm($alt) === $g) return true;
        }

        return false;
    }

    /** Mark the student's answers. $answers: question number → string | string[] */
    public static function mark(array $content, array $answers, bool $academic = false): array
    {
        $rows = [];
        $score = 0;
        foreach (static::answerKey($content) as $q => $k) {
            $given = $answers[$q] ?? null;
            if ($k['kind'] === 'blanks') {
                $given = array_map('strval', array_pad((array) ($given ?? []), count($k['answers']), ''));
                $ok = count($k['answers']) > 0;
                foreach ($k['answers'] as $b => $a) $ok = $ok && static::same($given[$b] ?? '', $a);
                $your = implode(' · ', array_filter(array_map('trim', $given), 'strlen'));
                $correct = implode(' · ', array_map(fn ($a) => str_replace('/', ' / ', $a), $k['answers']));
                if (isset($k['words'])) {
                    // lettered list: show "C – intuition" next to the letter
                    $show = fn ($l) => ($l = strtoupper(trim((string) $l))) === '' ? '' : $l . (isset($k['words'][ord($l[0]) - 65]) ? ' – ' . $k['words'][ord($l[0]) - 65] : '');
                    $your = $show($given[0] ?? '');
                    $correct = $show($k['answers'][0] ?? '');
                }
            } elseif ($k['kind'] === 'two') {
                $first = $k['first'] ?? $q;
                $chosen = array_values(array_unique(array_slice(array_filter((array) ($answers[$first] ?? []), 'is_string'), 0, 2)));
                $right = count(array_intersect($chosen, $k['answers']));
                $ok = $k['part'] === 1 ? $right >= 1 : $right >= 2;
                sort($chosen);
                $your = implode(', ', $chosen);
                $correct = implode(', ', $k['answers']);
            } elseif ($k['kind'] === 'heading') {
                $given = is_scalar($given) ? (string) $given : '';
                $ok = $given !== '' && $given === $k['answer'];
                $your = $given !== '' ? ($k['texts'][(int) $given] ?? '') : '';
                $correct = $k['answer'] !== '' ? ($k['texts'][(int) $k['answer']] ?? '') : '';
            } else {
                $your = is_string($given) ? $given : '';
                $ok = $your !== '' && $your === $k['answer'];
                $correct = $k['answer'];
            }
            $score += $ok ? 1 : 0;
            $rows[] = ['q' => $q, 'your' => $your, 'correct' => $correct, 'ok' => $ok];
        }

        return ['score' => $score, 'total' => count($rows), 'band' => static::band($score, count($rows), $academic), 'rows' => $rows];
    }

    /** IELTS Reading band – General Training chart, or the Academic chart when $academic is true (raw score out of 40; other totals are scaled to 40). */
    public static function band(int $score, int $total, bool $academic = false): string
    {
        if ($total <= 0) return '–';
        $s = (int) round($score * 40 / $total);
        if ($academic) {
            foreach ([[39, '9'], [37, '8.5'], [35, '8'], [33, '7.5'], [30, '7'], [27, '6.5'], [23, '6'], [19, '5.5'], [15, '5'], [13, '4.5'], [10, '4'], [8, '3.5'], [6, '3'], [4, '2.5'], [1, '2']] as [$min, $band]) {
                if ($s >= $min) return $band;
            }

            return '0';
        }
        foreach ([[40, '9'], [39, '8.5'], [37, '8'], [36, '7.5'], [34, '7'], [32, '6.5'], [30, '6'], [27, '5.5'], [23, '5'], [19, '4.5'], [15, '4'], [12, '3.5'], [9, '3'], [6, '2.5'], [1, '2']] as [$min, $band]) {
            if ($s >= $min) return $band;
        }

        return '0';
    }
}

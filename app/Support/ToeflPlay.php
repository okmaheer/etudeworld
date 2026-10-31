<?php

namespace App\Support;

/**
 * Turns saved TOEFL content into what the test page needs (never including correct answers),
 * and marks a student's answers on the server.
 *
 * Answer keys sent by the page:
 *   words        "<itemId>:<blankNumber>"   → the missing letters typed by the student
 *   mc / listening  "<itemId>:<questionNumber>" → index of the chosen option
 *   build        "<itemId>"                 → list of the words placed in the blanks
 */
class ToeflPlay
{
    private static function split(string $s): array
    {
        return array_values(array_filter(array_map('trim', explode('/', $s)), fn ($x) => $x !== ''));
    }

    /** The paragraph with every missing-letter word replaced by a marker the page fills in. */
    private static function wordsHtml(string $html): string
    {
        $i = 0;

        return preg_replace_callback('/([\p{L}\'’-]*)(#+)([\p{L}\'’-]*)/u', function ($m) use (&$i) {
            return '<span class="tf-blank" data-b="' . ($i++) . '" data-pre="' . e($m[1]) . '" data-len="' . strlen($m[2]) . '" data-suf="' . e($m[3]) . '"></span>';
        }, $html);
    }

    /** What the page gets for one section. */
    public static function payload(string $section, array $content, int $testId): array
    {
        $modules = Toefl::SECTIONS[$section]['modules'] ? [1, 2] : [0];
        $out = [];
        foreach ($modules as $m) {
            $n = 0;
            $items = [];
            foreach ($content['items'] ?? [] as $it) {
                if (($m ? (int) ($it['module'] ?? 1) : 0) !== $m) continue;
                $row = ['id' => $it['id'], 'type' => $it['type']];
                $ask = function (array $q) use (&$n, $it) {
                    $n++;
                    return ['n' => $n, 'key' => $it['id'] . ':' . $n, 'statement' => $q['statement'] ?? '', 'options' => $q['options'] ?? [],
                        'highlight' => $q['highlight'] ?? '', 'audio' => $q['audio'] ?? '', 'image' => $q['image'] ?? '',
                        'image_mode' => $q['image_mode'] ?? 'upload', 'graded' => $q['graded'] ?? true];
                };
                switch ($it['type']) {
                    case 'words':
                        $count = count(Toefl::blanks($it['paragraph'] ?? ''));
                        $row += ['title' => $it['title'] ?? '', 'html' => self::wordsHtml($it['paragraph'] ?? ''), 'n' => $n + 1, 'count' => $count];
                        $n += $count;
                        break;
                    case 'mc':
                        $row += array_intersect_key($it, array_flip(['kind', 'title', 'style', 'subtitle', 'author', 'to', 'from', 'date', 'subject', 'color', 'passage', 'image']));
                        $row['questions'] = array_map($ask, $it['questions'] ?? []);
                        break;
                    case 'response':
                        $row['questions'] = array_map($ask, $it['questions'] ?? []);
                        break;
                    case 'conversation': case 'announcement': case 'talk':
                        $row += array_intersect_key($it, array_flip(['title', 'audio', 'image', 'image_mode']));
                        $row['questions'] = array_map($ask, $it['questions'] ?? []);
                        break;
                    case 'build':
                        $n++;
                        $row += ['n' => $n, 'statement' => $it['statement'] ?? '', 'answer' => $it['answer'] ?? '', 'options' => self::split($it['options'] ?? ''), 'key' => $it['id']];
                        break;
                    default:
                        $n++;
                        $row += array_diff_key($it, ['id' => 1, 'type' => 1, 'module' => 1, 'graded' => 1]) + ['n' => $n];
                }
                $items[] = $row;
            }
            $out[] = ['module' => $m, 'minutes' => (int) ($content['minutes'][(string) $m] ?? 0), 'items' => $items, 'total' => $n,
                'instructions' => array_values(array_filter($content['instructions'] ?? [], fn ($b) => $m === 0 || (int) ($b['module'] ?? 1) === $m))];
        }

        return ['section' => $section, 'modules' => $out, 'minutes' => $content['minutes'] ?? []];
    }

    /** Mark the answers. Returns the score and one row per question for the results page. */
    public static function mark(string $section, array $content, array $answers): array
    {
        $rows = [];
        $score = $total = 0;
        $m = 0;
        $counter = [];
        foreach ($content['items'] ?? [] as $it) {
            $mod = Toefl::SECTIONS[$section]['modules'] ? (int) ($it['module'] ?? 1) : 0;
            $n = $counter[$mod] ?? 0;
            $add = function (array $r) use (&$rows, &$score, &$total, $mod) {
                $r['module'] = $mod;
                if ($r['graded']) { $total++; $score += $r['ok'] ? 1 : 0; }
                $rows[] = $r;
            };
            switch ($it['type']) {
                case 'words':
                    foreach (Toefl::blanks($it['paragraph'] ?? '') as $i => $b) {
                        $n++;
                        $right = strtolower(trim($it['answers'][$i] ?? ''));
                        $mine = strtolower(trim((string) ($answers[$it['id'] . ':' . ($i + 1)] ?? $answers[$it['id'] . ':' . $i] ?? '')));
                        $add(['n' => $n, 'type' => 'words', 'label' => $b['prefix'] . str_repeat('_', $b['len']) . $b['suffix'], 'yours' => $mine === '' ? '—' : $b['prefix'] . $mine . $b['suffix'],
                            'correct' => $b['prefix'] . $right . $b['suffix'], 'ok' => $right !== '' && $mine === $right, 'graded' => $it['graded'] ?? true]);
                    }
                    break;
                case 'mc': case 'response': case 'conversation': case 'announcement': case 'talk':
                    foreach ($it['questions'] ?? [] as $q) {
                        $n++;
                        $pick = $answers[$it['id'] . ':' . $n] ?? null;
                        $has = is_numeric($pick) && isset($q['options'][(int) $pick]);
                        $add(['n' => $n, 'type' => $it['type'], 'label' => $q['statement'], 'yours' => $has ? $q['options'][(int) $pick] : '—',
                            'correct' => $q['options'][$q['correct']] ?? '', 'ok' => $has && (int) $pick === (int) $q['correct'], 'graded' => ($q['graded'] ?? true) && $q['correct'] >= 0]);
                    }
                    break;
                case 'build':
                    $n++;
                    $right = self::split($it['correct'] ?? '');
                    $mine = array_values(array_map('trim', array_map('strval', (array) ($answers[$it['id']] ?? []))));
                    $fill = fn (array $w) => preg_replace_callback('/#/', function () use (&$w) { return array_shift($w) ?? '…'; }, $it['answer'] ?? '');
                    $add(['n' => $n, 'type' => 'build', 'label' => $it['statement'] ?? '', 'yours' => $mine ? $fill($mine) : '—', 'correct' => $fill($right),
                        'ok' => $mine && $mine === $right, 'graded' => true]);
                    break;
                default:
                    $n++;
            }
            $counter[$mod] = $n;
        }

        return ['score' => $score, 'total' => $total, 'rows' => $rows];
    }
}

<?php

namespace App\Support;

/**
 * Everything about the IELTS Speaking test content.
 *
 * Saved in ielts_test_modules.content (module = "speaking") like this:
 *   intro                    Main Section: instructions (filled in by default)
 *   cue                      optional "Start speaking now" audio (otherwise the browser says it)
 *   parts.1.intro            Part 1 – Section 1 (instructions)
 *   parts.1.questions[]      Part 1 – Section 2: any number of { text, audio }
 *   parts.2.intro            Part 2 – Section 1
 *   parts.2.question         Part 2 – Section 2 (one topic-card question, shown on screen)
 *   parts.3.intro / questions[]   Part 3 – same as Part 1
 *
 * On the website: Parts 1 and 3 PLAY each question's audio (the text is never shown – it is only
 * for the admin and the teacher), then the student records (up to 2 minutes, no timer shown,
 * Stop button). Part 2 shows the question with 1 minute of preparation, then up to 2 minutes of
 * recording with a timer. Recordings go to Admin → Evaluation Requests → IELTS Evaluation.
 */
class IeltsSpeaking
{
    /** Times in seconds. */
    public const PREP   = 60;    // Part 2 preparation
    public const RECORD = 120;   // longest recording per question (all parts)

    public const PARTS = [
        1 => ['name' => 'Introduction and Interview', 'many' => true],
        2 => ['name' => 'Individual Long Turn',       'many' => false],
        3 => ['name' => 'Two-way Discussion',         'many' => true],
    ];

    public static function defaultIntro(): string
    {
        return '<h3>IELTS Speaking Test Instructions</h3>'
            . '<p>The Speaking test has three parts and takes about 11–14 minutes.</p><ol>'
            . '<li><strong>Part 1:</strong> you will hear questions about yourself and familiar topics such as home, work, studies and hobbies.</li>'
            . '<li><strong>Part 2:</strong> you will see a topic card. You have 1 minute to prepare, then speak for up to 2 minutes.</li>'
            . '<li><strong>Part 3:</strong> you will hear more questions connected to the Part 2 topic. Give longer, more detailed answers.</li>'
            . '</ol><p>You need a microphone. When your browser asks, please click <strong>Allow</strong>. '
            . 'After each question you will hear “Start speaking now” and a beep – then start speaking. '
            . 'Your answers are recorded and sent to your teacher for evaluation.</p>';
    }

    public static function defaultPartIntro(int $part): string
    {
        return match ($part) {
            1 => '<h3>Part 1: Introduction and Interview</h3><p>In this part, you will hear some questions about yourself and everyday topics. '
                . 'You will hear each question – it is not shown on the screen, so listen carefully.</p>'
                . '<p>After each question, wait for “Start speaking now” and the beep, then answer. Click <strong>Stop</strong> when you have finished your answer.</p>',
            2 => '<h3>Part 2: Individual Long Turn</h3><p>In this part, you will see a topic card. You have <strong>1 minute</strong> to prepare and make notes.</p>'
                . '<p>Then speak about the topic for up to <strong>2 minutes</strong>. Recording stops automatically when the time is up.</p>',
            default => '<h3>Part 3: Two-way Discussion</h3><p>In this part, you will hear some more questions connected to the topic of Part 2.</p>'
                . '<p>Give full answers with reasons and examples. After each question, wait for the beep, then answer. Click <strong>Stop</strong> when you have finished.</p>',
        };
    }

    /** What the edit page shows: saved content, with default text for anything never saved. */
    public static function forEditing(?array $saved): array
    {
        $saved ??= [];
        $out = [
            'intro' => array_key_exists('intro', $saved) ? (string) $saved['intro'] : self::defaultIntro(),
            'cue'   => (string) ($saved['cue'] ?? ''),
            'parts' => [],
        ];
        foreach (self::PARTS as $p => $info) {
            $part = $saved['parts'][$p] ?? $saved['parts'][(string) $p] ?? [];
            $out['parts'][$p]['intro'] = array_key_exists('intro', $part) ? (string) $part['intro'] : self::defaultPartIntro($p);
            if ($info['many']) {
                $qs = array_map([self::class, 'question'], array_values((array) ($part['questions'] ?? [])));
                $out['parts'][$p]['questions'] = $qs ?: [['text' => '', 'audio' => '']];   // always show at least one box
            } else {
                $out['parts'][$p]['question'] = (string) ($part['question'] ?? '');
            }
        }

        return $out;
    }

    /** One Part 1 / Part 3 question as { text, audio } (older saves were text only). */
    private static function question($q): array
    {
        return is_array($q)
            ? ['text' => (string) ($q['text'] ?? ''), 'audio' => (string) ($q['audio'] ?? '')]
            : ['text' => (string) $q, 'audio' => ''];
    }

    /** Only keep audio paths that point to this website's own uploads. */
    public static function cleanPath($v): string
    {
        $v = trim((string) $v);

        return $v !== '' && preg_match('#^uploads/ielts/\d+/[\w./-]+$#', $v) && ! str_contains($v, '..') ? $v : '';
    }

    /** Clean what the edit page sends before saving (completely empty question boxes are dropped). */
    public static function fromRequest(array $input): array
    {
        $out = ['intro' => (string) ($input['intro'] ?? ''), 'cue' => self::cleanPath($input['cue'] ?? ''), 'parts' => []];
        foreach (self::PARTS as $p => $info) {
            $part = $input['parts'][$p] ?? [];
            $out['parts'][$p]['intro'] = (string) ($part['intro'] ?? '');
            if ($info['many']) {
                $qs = [];
                foreach ((array) ($part['questions'] ?? []) as $q) {
                    $q = self::question($q);
                    $q['audio'] = self::cleanPath($q['audio']);
                    if (self::filled($q['text']) || $q['audio'] !== '') $qs[] = $q;
                }
                $out['parts'][$p]['questions'] = $qs;
            } else {
                $out['parts'][$p]['question'] = (string) ($part['question'] ?? '');
            }
        }

        return $out;
    }

    /**
     * The questions of one part that students can take:
     * Parts 1 and 3 – questions with an audio file ({ text, audio }); Part 2 – the question text.
     */
    public static function questions(array $content, int $part): array
    {
        $p = $content['parts'][$part] ?? $content['parts'][(string) $part] ?? [];
        if (! self::PARTS[$part]['many']) {
            $q = (string) ($p['question'] ?? '');
            return self::filled($q) ? [$q] : [];
        }

        return array_values(array_filter(
            array_map([self::class, 'question'], array_values((array) ($p['questions'] ?? []))),
            fn ($q) => $q['audio'] !== ''
        ));
    }

    /** Part 1 / Part 3 questions that have text but no audio yet (students won't hear them). */
    public static function missingAudio(array $content, int $part): int
    {
        if (! self::PARTS[$part]['many']) return 0;
        $p = $content['parts'][$part] ?? $content['parts'][(string) $part] ?? [];

        return count(array_filter(
            array_map([self::class, 'question'], array_values((array) ($p['questions'] ?? []))),
            fn ($q) => $q['audio'] === '' && self::filled($q['text'])
        ));
    }

    /** Ready for students once every part has at least one question (Parts 1 and 3 need audio). */
    public static function isReady(?array $content): bool
    {
        foreach (array_keys(self::PARTS) as $p) {
            if (! self::questions($content ?? [], $p)) return false;
        }

        return true;
    }

    public static function filled(?string $html): bool
    {
        return filled(trim(html_entity_decode(strip_tags($html ?? '', '<img>'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\u{A0}"));
    }

    /** What the student page needs. Parts 1 and 3 send only the question AUDIO (the text is never shown). */
    public static function screens(array $saved): array
    {
        $c = self::forEditing($saved);
        $parts = [];
        foreach (self::PARTS as $p => $info) {
            $qs = self::questions($c, $p);
            $parts[] = [
                'part'      => $p,
                'title'     => 'Part ' . $p . ': ' . $info['name'],
                'intro'     => $c['parts'][$p]['intro'],
                'questions' => $info['many'] ? array_map(fn ($q) => asset($q['audio']), $qs) : $qs,
                'spoken'    => $info['many'],
            ];
        }

        return [
            'intro'  => $c['intro'],
            'cue'    => $c['cue'] !== '' ? asset($c['cue']) : null,
            'prep'   => self::PREP,
            'record' => self::RECORD,
            'parts'  => $parts,
        ];
    }
}

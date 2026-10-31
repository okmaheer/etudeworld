<?php

namespace App\Support;

/**
 * Everything about the CELPIP Speaking test content.
 *
 * Saved in celpip_test_modules.content (module = "speaking") like this:
 *   intro          Main Section: text (filled in by default) + optional audio
 *   tasks.{1-8}    text – the task question shown to the student
 *
 * On the website each task shows its question with a preparation countdown,
 * then records the student's answer with the microphone for the recording time.
 * Recordings are sent to Admin → CELPIP Evaluation.
 */
class CelpipSpeaking
{
    /**
     * The eight speaking tasks. Times in seconds: prep = preparation, record = speaking,
     * choose = (Task 5 only) time to pick one of the two options.
     * fields = what the admin fills in for that task.
     */
    public const TASKS = [
        1 => ['name' => 'Giving Advice',                       'prep' => 30, 'record' => 90, 'fields' => ['text']],
        2 => ['name' => 'Talking about a Personal Experience', 'prep' => 30, 'record' => 60, 'fields' => ['text']],
        3 => ['name' => 'Describing a Scene',                  'prep' => 30, 'record' => 60, 'fields' => ['text', 'image']],
        4 => ['name' => 'Making Predictions',                  'prep' => 30, 'record' => 60, 'fields' => ['text', 'image']],
        5 => ['name' => 'Comparing and Persuading',            'prep' => 60, 'record' => 60, 'choose' => 60,
              'fields' => ['intro', 'text', 'image1', 'caption1', 'image2', 'caption2']],
        6 => ['name' => 'Dealing with a Difficult Situation',  'prep' => 60, 'record' => 60, 'fields' => ['text']],
        7 => ['name' => 'Expressing Opinions',                 'prep' => 30, 'record' => 90, 'fields' => ['text']],
        8 => ['name' => 'Describing an Unusual Situation',     'prep' => 30, 'record' => 60, 'fields' => ['text', 'image']],
    ];

    /** Tasks that can be edited (all eight). */
    public const BUILT = [1, 2, 3, 4, 5, 6, 7, 8];

    /** Picture fields (file paths) – everything else is text. */
    private const IMAGE_FIELDS = ['image', 'image1', 'image2'];

    public static function defaultTask5Intro(): string
    {
        return '<h3>Instructions</h3><p>This task is made up of THREE parts:</p>'
            . '<ol><li>Choose an option</li><li>Preparation time</li><li>Speaking</li></ol>'
            . '<p>Click next to continue.</p>';
    }

    public static function defaultIntroText(): string
    {
        return '<h3>Speaking Test Instructions</h3><ol>'
            . '<li>For this sample test, you should use a timer to make sure that you complete each task within the given time.</li>'
            . '<li>In this sample test, no score will be provided for any of the Speaking tasks, and your answers will not be recorded. If you wish to record your own answers, record and save your responses using your computer microphone or your own recording device (cellphone, digital recorder, etc.).</li>'
            . '<li>On the official test, if you do not finish a task in the time provided, the screen will move to the next task. You cannot go back to the previous task. However, in this sample test, in order to move forward in the test you must click on “NEXT.”</li>'
            . '<li>Try to complete this practice Speaking Test in 15 minutes. For more information on test format, click <a href="https://www.celpip.ca/take-celpip/test-format/" target="_blank" rel="noopener">here</a>.</li>'
            . '</ol>';
    }

    /** What the edit page shows: saved content with defaults for anything never saved. */
    public static function forEditing(?array $saved): array
    {
        $saved ??= [];
        $out = [
            'intro' => [
                'text'  => array_key_exists('text', $saved['intro'] ?? []) ? (string) $saved['intro']['text'] : self::defaultIntroText(),
                'audio' => (string) ($saved['intro']['audio'] ?? ''),
                // played before every recording ("Start speaking now"); empty = the browser says it
                'cue'   => (string) ($saved['intro']['cue'] ?? ''),
            ],
            'tasks' => [],
        ];
        foreach (self::TASKS as $t => $info) {
            $task = $saved['tasks'][$t] ?? $saved['tasks'][(string) $t] ?? [];
            foreach ($info['fields'] as $f) {
                $out['tasks'][$t][$f] = array_key_exists($f, $task) ? (string) $task[$f]
                    : ($t === 5 && $f === 'intro' ? self::defaultTask5Intro() : '');
            }
        }

        return $out;
    }

    /** Clean what the edit page sends before saving. */
    public static function fromRequest(array $input): array
    {
        $path = function ($v) {
            $v = trim((string) $v);
            return $v !== '' && preg_match('#^(uploads/celpip/\d+/|audio/celpip/)[\w./-]+$#', $v) ? $v : '';
        };
        $out = ['intro' => [
            'text'  => (string) ($input['intro']['text'] ?? ''),
            'audio' => $path($input['intro']['audio'] ?? ''),
            'cue'   => $path($input['intro']['cue'] ?? ''),
        ], 'tasks' => []];
        foreach (self::TASKS as $t => $info) {
            foreach ($info['fields'] as $f) {
                $v = $input['tasks'][$t][$f] ?? '';
                $out['tasks'][$t][$f] = in_array($f, self::IMAGE_FIELDS, true) ? $path($v) : (string) $v;
            }
        }

        return $out;
    }

    /** Is this task filled in enough to show it to students? */
    public static function taskReady(int $t, array $task): bool
    {
        return $t === 5
            ? filled($task['image1'] ?? '') && filled($task['image2'] ?? '')
            : self::filledHtml($task['text'] ?? '');
    }

    public static function filledHtml(?string $html): bool
    {
        return filled(trim(strip_tags($html ?? '', '<img>')));
    }

    /** Has the admin written at least one task question? */
    public static function hasQuestions(?array $content): bool
    {
        foreach ($content['tasks'] ?? [] as $t => $task) {
            if (self::taskReady((int) $t, (array) $task)) return true;
        }

        return false;
    }

    /** What the student side needs: instructions + one screen per ready task. */
    public static function screens(array $saved): array
    {
        $c = self::forEditing($saved);
        $url = fn ($p) => filled($p) ? asset($p) : null;
        $tasks = [];
        foreach (self::TASKS as $t => $info) {
            $task = $c['tasks'][$t];
            if (! self::taskReady($t, $task)) continue;
            $image = $task['image'] ?? '';
            if ($t === 4 && ! filled($image)) $image = $c['tasks'][3]['image'];   // Task 4 normally uses Task 3's picture
            $tasks[] = array_filter([
                'task'     => $t,
                'title'    => 'Speaking Task ' . $t . ': ' . $info['name'],
                'text'     => $task['text'] ?? '',
                'image'    => $url($image),
                'prep'     => $info['prep'],
                'record'   => $info['record'],
                'choose'   => $info['choose'] ?? null,
                'intro'    => $t === 5 ? $task['intro'] : null,
                'options'  => $t === 5 ? [
                    ['image' => $url($task['image1']), 'caption' => $task['caption1']],
                    ['image' => $url($task['image2']), 'caption' => $task['caption2']],
                ] : null,
            ], fn ($v) => $v !== null);
        }

        return [
            'intro' => [
                'text'  => $c['intro']['text'],
                'audio' => $url($c['intro']['audio']),
                'cue'   => $url($c['intro']['cue']),
            ],
            'tasks' => $tasks,
        ];
    }
}

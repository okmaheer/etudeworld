<?php

namespace App\Support;

/**
 * Everything about the IELTS Writing test content.
 *
 * Saved in ielts_test_modules.content (module = "writing"):
 *   task1        the Task 1 question (left side; can include a graph/chart picture)
 *   task2        the Task 2 essay question
 * The right side of the student's screen is the answer box only.
 *
 * One timer for both tasks (1 hour); the student can switch tasks at any time. Each task has word limits:
 * the student cannot type past the maximum, and can only submit once both answers reach the minimum
 * (when time runs out, it is submitted anyway). No spell check, copy, paste, undo or redo.
 * Answers go to Admin → Evaluation Requests → IELTS Evaluation.
 */
class IeltsWriting
{
    public const TOTAL_MINUTES = 60;

    /** minutes = suggested time only (the timer is shared: 1 hour for both tasks). */
    public const TASKS = [
        'task1' => ['title' => 'Task 1', 'minutes' => 20, 'min' => 100, 'max' => 200],
        'task2' => ['title' => 'Task 2', 'minutes' => 40, 'min' => 180, 'max' => 350],
    ];

    /** "Report Writing" (Academic) / "Letter Writing" (General Training) / "Essay Writing". */
    public static function subtitle(string $task, string $format): string
    {
        if ($task === 'task2') return 'Essay Writing';

        return $format === 'gt' ? 'Letter Writing' : 'Report Writing';
    }

    public static function placeholder(string $task, string $format): string
    {
        if ($task === 'task2') {
            return 'e.g. Some people believe that unpaid community service should be a compulsory part of high school programmes. To what extent do you agree or disagree?';
        }

        return $format === 'gt'
            ? 'e.g. You recently stayed at a hotel and left something behind. Write a letter to the hotel manager. In your letter: • describe what you left • explain where you think you left it • say how it can be returned to you'
            : 'e.g. The chart below shows the number of visitors to three museums in London between 2010 and 2020. (Add the chart with the picture button.)';
    }

    /** What the edit page shows. */
    public static function forEditing(?array $saved, string $format = 'academic'): array
    {
        $saved ??= [];
        $out = [];
        foreach (array_keys(self::TASKS) as $task) {
            $out[$task] = (string) ($saved[$task] ?? '');
        }

        return $out;
    }

    public static function fromRequest(array $input): array
    {
        $out = [];
        foreach (array_keys(self::TASKS) as $task) {
            $out[$task] = (string) ($input[$task] ?? '');
        }

        return $out;
    }

    /** Ready for students once both tasks have a question. */
    public static function isReady(?array $content): bool
    {
        foreach (array_keys(self::TASKS) as $task) {
            if (! IeltsSpeaking::filled($content[$task] ?? '')) return false;
        }

        return true;
    }

    public static function words(string $text): int
    {
        return preg_match_all('/\S+/u', $text);
    }
}

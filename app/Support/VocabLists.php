<?php

namespace App\Support;

use App\Http\Controllers\VocabularyController;
use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\VocabTopic;
use App\Models\VocabWord;
use Illuminate\Support\Facades\Auth;

/**
 * One list of words students can study: an IELTS test section, a CELPIP test section or a Writing/Speaking topic.
 * Every list has the same shape, so the student pages work the same for all of them.
 */
class VocabLists
{
    public const EXAMS = ['ielts' => 'IELTS', 'celpip' => 'CELPIP'];

    public static function ielts(IeltsTest $t, string $m): array
    {
        $u = ['module' => $m, 'test' => $t->id];

        return static::shape("ielts:$m:{$t->id}", 'ielts', $m, 'test', $t->id, $t->formatShort() . ' ' . $t->moduleLabel(), $t->moduleLabel(),
            VocabularyController::words($t, $m), route('vocab.test', $u), route('vocab.exercises', $u), route('vocab.progress', $u), route('ielts.vocab.data', $u), $t->formatShort() === 'GT' ? 'IELTS General Training' : 'IELTS Academic');
    }

    public static function celpip(CelpipTest $t, string $m): array
    {
        $u = ['module' => $m, 'test' => $t->id];

        return static::shape("celpip:$m:{$t->id}", 'celpip', $m, 'test', $t->id, 'CELPIP ' . $t->formatShort() . ' ' . $t->moduleLabel(), $t->moduleLabel(),
            VocabularyController::celpipWords($t, $m), route('vocab.celpip.test', $u), route('vocab.celpip.exercises', $u), route('vocab.celpip.progress', $u), route('vocab.celpip.data', $u), $t->format === 'ls' ? 'CELPIP LS' : 'CELPIP General');
    }

    public static function topic(VocabTopic $t): array
    {
        $words = $t->isVisible() ? VocabWord::where('topic_id', $t->id)->where('approved', true)->orderBy('id')->get() : collect();
        $u = ['exam' => $t->exam, 'skill' => $t->skill, 'slug' => $t->slug];

        return static::shape("topic:{$t->id}", $t->exam, $t->skill, 'topic', $t->id, strtoupper($t->exam) . ' ' . ucfirst($t->skill) . ': ' . $t->name, $t->name,
            $words, route('vocab.topic', $u), route('vocab.topic.exercises', $u), route('vocab.topic.progress', $u), route('vocab.topic.data', $u), strtoupper($t->exam));
    }

    /** Find a list from a saved progress key ("ielts:listening:2", "celpip:reading:3", "topic:5"). */
    public static function fromKey(string $key): ?array
    {
        $p = explode(':', $key);
        if ($p[0] === 'topic') {
            $t = VocabTopic::find((int) ($p[1] ?? 0));

            return $t ? static::topic($t) : null;
        }
        if (count($p) !== 3) return null;
        if ($p[0] === 'ielts') {
            $t = IeltsTest::find((int) $p[2]);

            return $t ? static::ielts($t, $p[1]) : null;
        }
        $t = CelpipTest::find((int) $p[2]);

        return $t ? static::celpip($t, $p[1]) : null;
    }

    /** Previous and next list (links) among the lists of the same exam type and skill that have words. */
    public static function neighbours(array $list): array
    {
        $items = [];
        if ($list['kind'] === 'topic') {
            foreach (VocabTopic::where('exam', $list['exam'])->where('skill', $list['skill'])->orderBy('name')->get() as $t) {
                $l = static::topic($t);
                if ($l['words']->isNotEmpty()) $items[] = $l;
            }
        } else {
            $model = $list['exam'] === 'celpip' ? CelpipTest::class : IeltsTest::class;
            $format = $model::find($list['id'])->format;
            foreach ($model::where('format', $format)->get()->sortBy(fn ($t) => (int) preg_replace('/\D/', '', $t->moduleLabel())) as $t) {
                $l = $list['exam'] === 'celpip' ? static::celpip($t, $list['skill']) : static::ielts($t, $list['skill']);
                if ($l['words']->isNotEmpty()) $items[] = $l;
            }
        }
        $at = null;
        foreach ($items as $i => $l) if ($l['key'] === $list['key']) $at = $i;
        if ($at === null) return [null, null];

        return [$items[$at - 1] ?? null, $items[$at + 1] ?? null];
    }

    private static function shape(string $key, string $exam, string $skill, string $kind, int $id, string $label, string $heading, $words, string $show, string $exercises, string $progress, string $data, string $examTitle): array
    {
        return [
            'key' => $key, 'exam' => $exam, 'examLabel' => static::EXAMS[$exam], 'examTitle' => $examTitle, 'skill' => $skill, 'skillLabel' => ucfirst($skill), 'kind' => $kind, 'id' => $id,
            'label' => $label, 'heading' => $heading, 'words' => $words,
            'show' => $show, 'exercises' => $exercises, 'progress' => $progress, 'data' => $data,
            'skillUrl' => route('vocab.' . $exam . '.' . $skill), 'examUrl' => route('vocab.' . $exam),
        ];
    }
}

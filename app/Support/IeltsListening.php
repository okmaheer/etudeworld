<?php

namespace App\Support;

/**
 * IELTS Listening. It works exactly like IeltsReading (question groups, numbering, marking) with these differences:
 *   • 4 parts instead of 3 passages, 32 minutes, one audio file for the whole test (content["audio"])
 *   • only these question types: map labelling, matching statement (drag and drop), FITB, flow chart, table, MCQ, MCQ-5 
 *   • different band chart
 */
class IeltsListening extends IeltsReading
{
    public const MINUTES = 32;

    public const PARTS = [1, 2, 3, 4];

    public const MCQ_OPTIONS = 3;

    /** Types that use the two-column layout in Parts 2 and 3 (their forms are added later). */
    public const SPLIT_TYPES = ['map', 'mapdrop', 'drag'];

    public const TYPES = [
        'drag'       => ['button' => 'Add Matching Statement', 'label' => 'Matching statements (drag and drop)'],
        'map'        => ['button' => 'Add Map Labelling',     'label' => 'Map labelling'],
        'mapdrop'    => ['button' => 'Add Map Labelling – Drop Ver', 'label' => 'Map labelling (drag and drop)'],
        'fitb'       => ['button' => 'Add FITB',              'label' => 'Fill in the blanks'],
        'fitb_flow'  => ['button' => 'Add FITB – Flow Chart', 'label' => 'Fill in the blanks – flow chart'],
        'fitb_table' => ['button' => 'Add FITB – Table',      'label' => 'Fill in the blanks – table'],
        'mcq'        => ['button' => 'Add MCQ',               'label' => 'Multiple choice (A–C)'],
        'mcq5'       => ['button' => 'Add MCQ – 5 Options',   'label' => 'Multiple choice – choose TWO (A–E)'],
    ];

    /** Students can take the test once the audio is attached and there is at least one question. */
    public static function isReady(?array $saved): bool
    {
        $c = static::normalize($saved);

        return $c['audio'] !== '' && static::total($c) > 0;
    }

    /** IELTS Listening band (raw score out of 40; other totals are scaled to 40). */
    public static function band(int $score, int $total, bool $academic = false): string
    {
        if ($total <= 0) return '–';
        $s = (int) round($score * 40 / $total);
        foreach ([[39, '9'], [37, '8.5'], [35, '8'], [32, '7.5'], [30, '7'], [26, '6.5'], [23, '6'], [18, '5.5'], [16, '5'], [13, '4.5'], [11, '4'], [8, '3.5'], [6, '3'], [4, '2.5'], [1, '2']] as [$min, $band]) {
            if ($s >= $min) return $band;
        }

        return '0';
    }
}

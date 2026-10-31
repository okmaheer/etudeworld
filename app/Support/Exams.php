<?php

namespace App\Support;

/** The language exams on the website (used for the student portal tabs and results). */
class Exams
{
    public const ALL = [
        'ielts'  => ['label' => 'IELTS',          'language' => 'English', 'route' => 'ielts'],
        'celpip' => ['label' => 'CELPIP',         'language' => 'English', 'route' => 'celpip'],
        'toefl'  => ['label' => 'TOEFL',          'language' => 'English', 'route' => 'toefl'],
    ];
}

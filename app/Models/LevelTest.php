<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelTest extends Model
{
    protected $guarded = [];

    protected $casts = ['thresholds' => 'array'];

    /** CEFR levels with the share of the total score needed to reach each one (from the 40-mark guide: 8, 15, 23, 31, 37) */
    public const LEVELS = [
        'A1' => [0,     'Beginner. Can use very basic expressions and introduce yourself.'],
        'A2' => [0.2,   'Elementary. Can communicate in simple, routine situations.'],
        'B1' => [0.375, 'Intermediate. Can deal with most everyday situations and describe experiences.'],
        'B2' => [0.575, 'Upper-Intermediate. Can interact fluently and produce clear, detailed text.'],
        'C1' => [0.775, 'Advanced. Can express ideas fluently and use language flexibly for complex purposes.'],
        'C2' => [0.925, 'Proficiency. Can understand and express virtually anything with precision and ease.'],
    ];

    public function sections()
    {
        return $this->hasMany(LevelTestSection::class)->orderBy('number');
    }

    public function section(int $n): LevelTestSection
    {
        return $this->sections()->firstOrCreate(['number' => $n]);
    }

    public function questionCount(): int
    {
        return LevelTestQuestion::whereIn('level_test_section_id', $this->sections()->pluck('id'))->count();
    }

    /** Lowest score for each level, for a test with $total questions (manual cut-offs win over the automatic ones) */
    public function cutoffs(int $total): array
    {
        $out = [];
        $prev = 0;
        foreach (self::LEVELS as $level => [$share]) {
            $min = (int) ceil($share * $total - 1e-9);
            $manual = $this->thresholds[$level] ?? null;
            if ($manual !== null && $manual !== '') {
                $min = (int) $manual;
            }
            $out[$level] = $level === 'A1' ? 0 : max($min, $prev);
            $prev = $out[$level];
        }

        return $out;
    }

    public function levelFor(int $score, int $total): string
    {
        $found = 'A1';
        foreach ($this->cutoffs($total) as $level => $min) {
            if ($score >= $min) {
                $found = $level;
            }
        }

        return $found;
    }
}

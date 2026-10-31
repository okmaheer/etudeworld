<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelTestQuestion extends Model
{
    protected $guarded = [];

    protected $casts = ['options' => 'array'];

    public function section()
    {
        return $this->belongsTo(LevelTestSection::class, 'level_test_section_id');
    }

    /** Is the student's answer right? (fill-in-the-blank ignores case, spaces and end punctuation) */
    public function isCorrect(?string $given): bool
    {
        $given = trim((string) $given);
        if ($given === '') {
            return false;
        }
        if ($this->type === 'mcq') {
            return $given === (string) $this->answer;
        }
        $clean = fn ($s) => preg_replace('/\s+/', ' ', mb_strtolower(trim($s, " \t\n\r.!?,;:")));
        foreach (explode('|', $this->answer) as $ok) {
            if ($clean($given) === $clean($ok)) {
                return true;
            }
        }

        return false;
    }

    /** The answer written out for the results page */
    public function answerText(): string
    {
        if ($this->type === 'mcq') {
            $i = (int) $this->answer;

            return chr(97 + $i) . ') ' . ($this->options[$i] ?? '');
        }

        return str_replace('|', ' / ', $this->answer);
    }
}

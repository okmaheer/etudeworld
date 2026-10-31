<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Which words of one vocabulary list (test + skill) a student has marked as learned. */
class StudentVocabProgress extends Model
{
    protected $table = 'student_vocab_progress';

    protected $fillable = ['student_id', 'ielts_test_id', 'module', 'list_key', 'known'];

    protected $casts = ['known' => 'array'];

    public function test()
    {
        return $this->belongsTo(IeltsTest::class, 'ielts_test_id');
    }
}

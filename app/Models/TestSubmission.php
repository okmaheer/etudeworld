<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * One student's Writing or Speaking answers for one test, waiting for evaluation.
 * Speaking recordings are stored privately in storage/app/submissions/{id}/.
 */
class TestSubmission extends Model
{
    public const EXAMS   = ['celpip' => 'CELPIP', 'ielts' => 'IELTS', 'toefl' => 'TOEFL'];
    public const MODULES = ['writing' => 'Writing', 'speaking' => 'Speaking'];

    protected $fillable = [
        'student_id', 'exam', 'test_id', 'test_name', 'mode', 'module', 'attempt',
        'student_name', 'student_email', 'answers', 'status', 'score', 'feedback', 'evaluated_at',
    ];

    protected $casts = [
        'answers'      => 'array',
        'evaluated_at' => 'datetime',
    ];

    public function folder(): string
    {
        return storage_path('app/submissions/' . $this->id);
    }

    public function isEvaluated(): bool
    {
        return $this->status === 'evaluated';
    }

    protected static function booted(): void
    {
        // Remove the recordings when a submission is deleted.
        static::deleted(fn (self $s) => File::deleteDirectory($s->folder()));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The questions of one section (e.g. Speaking) of one IELTS test. */
class IeltsTestModule extends Model
{
    protected $fillable = ['ielts_test_id', 'module', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(IeltsTest::class, 'ielts_test_id');
    }
}

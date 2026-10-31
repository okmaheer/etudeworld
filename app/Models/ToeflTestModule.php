<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToeflTestModule extends Model
{
    protected $fillable = ['toefl_test_id', 'module', 'slug', 'meta_title', 'meta_description', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ToeflTest::class, 'toefl_test_id');
    }
}

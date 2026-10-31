<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CelpipTestModule extends Model
{
    protected $fillable = ['celpip_test_id', 'module', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(CelpipTest::class, 'celpip_test_id');
    }

    /** True when at least one question has been written. */
    public function hasContent(): bool
    {
        $filled = false;
        $content = $this->content ?? [];
        array_walk_recursive($content, function ($v) use (&$filled) {
            if (! $filled && filled(trim(strip_tags((string) $v, '<img>')))) $filled = true;
        });

        return $filled;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelTestSection extends Model
{
    protected $guarded = [];

    protected $casts = ['passages' => 'array'];

    public function test()
    {
        return $this->belongsTo(LevelTest::class, 'level_test_id');
    }

    public function questions()
    {
        return $this->hasMany(LevelTestQuestion::class)
            ->orderByRaw('COALESCE(passage, 9)')->orderBy('position')->orderBy('id');
    }
}

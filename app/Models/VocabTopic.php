<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** A Writing / Speaking vocabulary topic (for example "Environment"). Not tied to one test. */
class VocabTopic extends Model
{
    public const SKILLS = ['writing' => 'Writing', 'speaking' => 'Speaking'];

    protected $fillable = ['exam', 'skill', 'name', 'slug', 'status'];

    public function words()
    {
        return $this->hasMany(VocabWord::class, 'topic_id');
    }

    public static function makeSlug(string $exam, string $skill, string $name): string
    {
        $base = Str::slug($name) ?: 'topic';
        $slug = $base;
        for ($i = 2; static::where(compact('exam', 'skill', 'slug'))->exists(); $i++) $slug = $base . '-' . $i;

        return $slug;
    }

    public function isVisible(): bool
    {
        return $this->status === 'active' || Auth::check();
    }
}

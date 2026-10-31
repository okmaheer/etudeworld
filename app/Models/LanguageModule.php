<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LanguageModule extends Model
{
    /** Languages that have a learning page. Add an entry here to open another language. */
    public const LANGUAGES = [
        'english' => [
            'name'    => 'English',
            'flag'    => 'EN',
            'tagline' => 'Build everyday fluency, grammar and vocabulary for work, study and travel.',
            'intro'   => 'Learn English step by step. Pick a module below, then work through its lessons at your own pace.',
        ],
        'french' => [
            'name'    => 'French',
            'flag'    => 'FR',
            'tagline' => 'Useful for Canadian immigration points and life in Québec.',
            'intro'   => 'Learn French step by step. Pick a module below, then work through its lessons at your own pace.',
        ],
        'chinese' => [
            'name'    => 'Chinese',
            'flag'    => 'ZH',
            'tagline' => 'The most spoken language on Earth, with a rich culture behind every character.',
            'intro'   => 'Learn Chinese (Mandarin) step by step. Pick a module below, then work through its lessons at your own pace.',
        ],
        'german' => [
            'name'    => 'German',
            'flag'    => 'DE',
            'tagline' => 'Open doors to study and careers across Germany, Austria and Switzerland.',
            'intro'   => 'Learn German step by step. Pick a module below, then work through its lessons at your own pace.',
        ],
    ];

    protected $fillable = ['language', 'title', 'slug', 'icon', 'description', 'sort_order', 'is_published', 'meta_title', 'meta_description'];

    protected $casts = ['is_published' => 'boolean'];

    public function lessons(): HasMany
    {
        return $this->hasMany(LanguageLesson::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForLanguage($q, string $language)
    {
        return $q->where('language', $language)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }

    public function getUrlAttribute(): string
    {
        return url('languages/' . $this->language . '/' . $this->slug);
    }
}

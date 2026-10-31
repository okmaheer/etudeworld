<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanguageLesson extends Model
{
    public const LEVELS = ['A1' => 'A1 – Beginner', 'A2' => 'A2 – Elementary', 'B1' => 'B1 – Intermediate', 'B2' => 'B2 – Upper-intermediate', 'C1' => 'C1 – Advanced'];

    protected $fillable = ['language_module_id', 'title', 'slug', 'level', 'summary', 'body', 'sort_order', 'is_published', 'meta_title', 'meta_description'];

    protected $casts = ['is_published' => 'boolean'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(LanguageModule::class, 'language_module_id');
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }

    public function getUrlAttribute(): string
    {
        return url('languages/' . $this->module->language . '/' . $this->module->slug . '/' . $this->slug);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InfoPage extends Model
{
    protected $fillable = ['title', 'slug', 'is_system', 'body', 'meta_title', 'meta_description', 'is_published'];

    protected $casts = ['is_system' => 'boolean', 'is_published' => 'boolean'];

    /** Built-in pages keep their old addresses; new pages live under /page/. */
    public function getPublicPathAttribute(): string
    {
        return $this->is_system ? '/' . $this->slug : '/page/' . $this->slug;
    }

    /** Visitors only see a page that is published and has some content. */
    public function getIsLiveAttribute(): bool
    {
        return $this->is_published && trim(strip_tags((string) $this->body, '<img><iframe><table>')) !== '';
    }
}

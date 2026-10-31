<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    /** Social networks shown on the author block / page. */
    public const SOCIALS = [
        'facebook' => 'Facebook',
        'x'        => 'X',
        'linkedin' => 'LinkedIn',
    ];

    protected $fillable = ['name', 'slug', 'bio', 'image', 'facebook', 'x', 'linkedin'];

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    /** Only the social links that have been filled in. */
    public function getSocialLinksAttribute(): array
    {
        return collect(self::SOCIALS)
            ->filter(fn ($label, $key) => filled($this->{$key}))
            ->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'url' => $this->{$key}])
            ->values()
            ->all();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }
}

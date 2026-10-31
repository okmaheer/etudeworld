<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A paid client: an email address that may open the paid tests of ONE exam between two dates. */
class Client extends Model
{
    public const EXAMS = ['ielts' => 'IELTS', 'celpip' => 'CELPIP', 'toefl' => 'TOEFL'];

    protected $fillable = ['email', 'name', 'phone', 'exam', 'days', 'starts_at', 'ends_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('starts_at', '<=', now())->where('ends_at', '>=', now());
    }

    public function isActive(): bool
    {
        return $this->starts_at <= now() && $this->ends_at >= now();
    }

    /** Added today with N days → the last day of access is today + N (access lasts until the end of that day). */
    public static function endFor(int $days): \Illuminate\Support\Carbon
    {
        return now()->startOfDay()->addDays($days)->endOfDay();
    }
}

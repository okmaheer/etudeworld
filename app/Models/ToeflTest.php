<?php

namespace App\Models;

use App\Support\Toefl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/** A TOEFL iBT (2026 format) practice test with four sections: Reading, Listening, Writing, Speaking. */
class ToeflTest extends Model
{
    public const TYPES = ['free' => 'Free', 'paid' => 'Paid'];

    public const STATUSES = ['active' => 'Active', 'draft' => 'Draft'];

    protected $fillable = ['name', 'slug', 'type', 'status'];

    public function modules(): HasMany
    {
        return $this->hasMany(ToeflTestModule::class);
    }

    /** Visitors only see Active tests; a logged-in admin also sees Drafts. */
    public function scopeVisible(Builder $query): Builder
    {
        return Auth::check() ? $query : $query->where('status', 'active');
    }

    public function isLocked(): bool
    {
        return $this->type === 'paid' && ! \App\Support\PaidAccess::allows('toefl');
    }

    public function moduleRow(string $module): ?ToeflTestModule
    {
        return $this->relationLoaded('modules')
            ? $this->modules->firstWhere('module', $module)
            : $this->modules()->where('module', $module)->first();
    }

    public function moduleContent(string $module): array
    {
        return $this->moduleRow($module)?->content ?? [];
    }

    /** Has this section got at least one question? */
    public function moduleFilled(string $module): bool
    {
        return Toefl::hasQuestions($module, $this->moduleContent($module));
    }

    /** "Test 3" for a test named "... Test 3"; otherwise its place in the list. */
    public function moduleLabel(): string
    {
        if (preg_match('/(\d+)\s*$/', trim($this->name), $m)) return 'Test ' . (int) $m[1];

        return 'Test ' . static::where('id', '<=', $this->id)->count();
    }
}

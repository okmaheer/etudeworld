<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/** An IELTS practice test (Academic or General Training). Listening, Reading, Writing and Speaking are built. */
class IeltsTest extends Model
{
    public const FORMATS = [
        'academic' => 'IELTS Academic',
        'gt'       => 'IELTS General Training',
    ];

    public const TYPES = [
        'free' => 'Free',
        'paid' => 'Paid',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'draft'  => 'Draft',
    ];

    /** The four modules. Only those listed in BUILT have question pages so far. */
    public const MODULES = [
        'listening' => ['label' => 'Listening', 'icon' => 'headphones'],
        'reading'   => ['label' => 'Reading',   'icon' => 'pen'],
        'writing'   => ['label' => 'Writing',   'icon' => 'page'],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic'],
    ];

    public const BUILT = ['listening', 'reading', 'writing', 'speaking'];

    /** Reading, Writing and Speaking are built for both Academic and General Training. */
    public function hasModule(string $module): bool
    {
        return in_array($module, self::BUILT, true);
    }

    /** Admin page for one skill's questions. */
    public function adminModuleUrl(string $module): string
    {
        return in_array($module, ['reading', 'listening'], true) ? route('admin.ielts.' . $module, $this) : route('admin.ielts.module', [$this, $module]);
    }

    /** The question pages this test has, in order. */
    public function builtModules(): array
    {
        return array_values(array_filter(self::BUILT, fn ($m) => $this->hasModule($m)));
    }

    /** What students see about each skill on the practice pages (real IELTS timings). */
    public const PUBLIC_MODULES = [
        'listening' => ['label' => 'Listening', 'icon' => 'headphones', 'time' => '32 minutes', 'parts' => '4 parts',
                        'desc' => 'Listen to conversations and talks, then answer 40 questions.'],
        'reading'   => ['label' => 'Reading',   'icon' => 'pen',        'time' => '60 minutes', 'parts' => '3 sections',
                        'desc' => 'Read three passages and answer 40 questions.'],
        'writing'   => ['label' => 'Writing',   'icon' => 'page',       'time' => '1 hour', 'parts' => '2 tasks',
                        'desc' => 'Complete Task 1 and an essay for Task 2 in 1 hour.'],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic',        'time' => '11–14 minutes', 'parts' => '3 parts',
                        'desc' => 'Answer interview questions, speak about a topic card, then discuss it further.'],
    ];

    protected $fillable = ['name', 'slug', 'format', 'type', 'meta_title', 'meta_description', 'status'];

    /** Visitors only see Active tests; a logged-in admin also sees Drafts. */
    public function scopeVisible(Builder $query): Builder
    {
        return Auth::check() ? $query : $query->where('status', 'active');
    }

    /** "Module 3" for a test named "... Test 3"; otherwise its place among tests of the same format. */
    public function moduleLabel(): string
    {
        if (preg_match('/(\d+)\s*$/', trim($this->name), $m)) {
            return 'Module ' . (int) $m[1];
        }
        $n = static::where('format', $this->format)->where('id', '<=', $this->id)->count();

        return 'Module ' . $n;
    }

    /** Short label for badges: "Academic" / "GT". */
    public function formatShort(): string
    {
        return $this->format === 'gt' ? 'GT' : 'Academic';
    }

    /** The test's address on the website (the practice pages are built later). */
    public function publicPath(): string
    {
        return '/ielts/' . $this->formatPath() . '/' . $this->slug;
    }

    /** Website address part for the format: "academic" or "general-training". */
    public function formatPath(): string
    {
        return $this->format === 'gt' ? 'general-training' : 'academic';
    }

    /** A paid test is locked unless the visitor is an admin or a student with active paid access to this exam (see PaidAccess). */
    public function isLocked(): bool
    {
        return $this->type === 'paid' && ! \App\Support\PaidAccess::allows('ielts');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(IeltsTestModule::class);
    }

    /** The saved questions of one section (empty array if nothing saved yet). */
    public function moduleContent(string $module): array
    {
        $row = $this->relationLoaded('modules')
            ? $this->modules->firstWhere('module', $module)
            : $this->modules()->where('module', $module)->first();

        return $row->content ?? [];
    }

    /** Is this section ready for students (questions written)? */
    public function moduleReady(string $module): bool
    {
        if (! $this->hasModule($module)) return false;

        return match ($module) {
            'listening' => \App\Support\IeltsListening::isReady($this->moduleContent('listening')),
            'reading'  => \App\Support\IeltsReading::isReady($this->moduleContent('reading')),
            'speaking' => \App\Support\IeltsSpeaking::isReady($this->moduleContent('speaking')),
            'writing'  => \App\Support\IeltsWriting::isReady($this->moduleContent('writing')),
            default    => false,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class CelpipTest extends Model
{
    public const FORMATS = [
        'general' => 'CELPIP General',
        'ls'      => 'CELPIP LS',
    ];

    public const TYPES = [
        'free' => 'Free',
        'paid' => 'Paid',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'draft'  => 'Draft',
    ];

    /**
     * The four test modules. CELPIP-LS only has Listening and Speaking,
     * so Writing and Reading are switched off for LS tests.
     */
    public const MODULES = [
        'writing'   => ['label' => 'Writing',   'icon' => 'page',       'ls' => false],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic',        'ls' => true],
        'reading'   => ['label' => 'Reading',   'icon' => 'pen',        'ls' => false],
        'listening' => ['label' => 'Listening', 'icon' => 'headphones', 'ls' => true],
    ];

    protected $fillable = [
        'name', 'slug', 'format', 'type', 'meta_title', 'meta_description', 'status',
    ];

    /** Writing tasks (as in the real CELPIP test). */
    public const WRITING_TASKS = [
        'task1' => ['title' => 'Task 1', 'subtitle' => 'Writing an Email', 'time' => '27 minutes', 'words' => '150–200 words'],
        'task2' => ['title' => 'Task 2', 'subtitle' => 'Responding to Survey Questions', 'time' => '26 minutes', 'words' => '150–200 words'],
    ];

    /**
     * How each section appears on the live website, in the real CELPIP order
     * (Listening → Reading → Writing → Speaking).
     */
    public const PUBLIC_MODULES = [
        'listening' => ['label' => 'Listening', 'icon' => 'headphones', 'time' => 'about 47 minutes', 'parts' => '6 parts',
                        'desc' => 'Listen to everyday conversations, news and discussions, then answer questions.'],
        'reading'   => ['label' => 'Reading',   'icon' => 'pen',        'time' => 'about 55 minutes', 'parts' => '4 parts',
                        'desc' => 'Read letters, diagrams, information and viewpoints, then answer questions.'],
        'writing'   => ['label' => 'Writing',   'icon' => 'page',       'time' => '53 minutes',       'parts' => '2 tasks',
                        'desc' => 'Write an email, then respond to a survey by choosing one of two options.'],
        'speaking'  => ['label' => 'Speaking',  'icon' => 'mic',        'time' => 'about 20 minutes', 'parts' => '8 tasks',
                        'desc' => 'Give advice, describe scenes, make predictions and express opinions.'],
    ];

    /** Writing task timers in minutes (real CELPIP timings). */
    public const WRITING_MINUTES = ['task1' => 27, 'task2' => 26];

    /** Visitors only see Active tests; a logged-in admin also sees Drafts (to preview them). */
    public function scopeVisible(Builder $query): Builder
    {
        return Auth::check() ? $query : $query->where('status', 'active');
    }

    /**
     * Is this test locked for the current visitor?
     * Paid tests are locked until Paid Clients is built; a logged-in admin can always open them.
     * (Later: also unlocked for students with an active paid plan.)
     */
    public function isLocked(): bool
    {
        return $this->type === 'paid' && ! \App\Support\PaidAccess::allows('celpip');
    }

    /** The sections this test has, in exam order (LS tests: Listening + Speaking only). */
    public function examModules(): array
    {
        return array_values(array_filter(array_keys(self::PUBLIC_MODULES), fn ($m) => $this->hasModule($m)));
    }

    /** Saved questions of one section, e.g. moduleContent('writing')['task1']. */
    public function moduleContent(string $module): array
    {
        $row = $this->relationLoaded('modules')
            ? $this->modules->firstWhere('module', $module)
            : $this->modules()->where('module', $module)->first();

        return $row->content ?? [];
    }

    /** Is this section ready to be taken on the website? (Only Writing can be built so far.) */
    public function moduleReady(string $module): bool
    {
        return $this->hasModule($module) && in_array($module, ['writing', 'listening', 'reading', 'speaking'], true) && $this->moduleFilled($module);
    }

    /** "Module 3" for a test named "... Test 3"; otherwise its place among tests of the same format. */
    public function moduleLabel(): string
    {
        if (preg_match('/(\d+)\s*$/', trim($this->name), $m)) return 'Module ' . (int) $m[1];

        return 'Module ' . static::where('format', $this->format)->where('id', '<=', $this->id)->count();
    }

    public function formatShort(): string
    {
        return $this->format === 'ls' ? 'LS' : 'General';
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CelpipTestModule::class);
    }

    /** Has this section (writing, speaking…) got any questions saved yet? */
    public function moduleFilled(string $module): bool
    {
        $row = $this->relationLoaded('modules')
            ? $this->modules->firstWhere('module', $module)
            : $this->modules()->where('module', $module)->first();

        if ($module === 'listening') {
            return \App\Support\CelpipListening::hasQuestions($row?->content);
        }
        if ($module === 'reading') {
            return \App\Support\CelpipReading::hasQuestions($row?->content);
        }
        if ($module === 'speaking') {
            return \App\Support\CelpipSpeaking::hasQuestions($row?->content);
        }

        return $row ? $row->hasContent() : false;
    }

    public function hasModule(string $module): bool
    {
        if (! isset(self::MODULES[$module])) {
            return false;
        }

        return $this->format !== 'ls' || self::MODULES[$module]['ls'];
    }
}

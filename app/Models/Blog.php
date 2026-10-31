<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    /** Normal blog categories (address: /blog/{category}/{slug}). */
    public const CATEGORIES = [
        'ielts'             => 'IELTS',
        'celpip'            => 'CELPIP',
        'toefl'             => 'TOEFL',
        'study-methods'     => 'Study Methods',
        'language-learning' => 'Language Learning',
    ];

    public const STATUSES = [
        'published' => 'Published',
        'draft'     => 'Draft',
    ];

    protected $fillable = [
        'title', 'slug', 'category', 'author_id', 'content', 'meta_title', 'meta_description',
        'canonical_url', 'image', 'image_alt', 'status', 'published_at', 'topic',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    /** Has the "topic" column been added yet (php artisan migrate)? Until then the topic features are simply off instead of crashing. */
    public static function hasTopicColumn(): bool
    {
        static $has = null;

        return $has ??= \Illuminate\Support\Facades\Schema::hasColumn('blogs', 'topic');
    }

    /** Sample answers only: is this category one of the Sample Answers sections? */
    public static function isSampleCategory(?string $category): bool
    {
        return $category !== null && str_starts_with($category, 'sa-');
    }

    /**
     * Cleans the topic typed by the admin. If the same topic already exists with another spelling
     * ("transport" / "Transport"), the existing spelling is used, so one topic never splits in two.
     */
    public static function cleanTopic(?string $topic, ?int $ignoreId = null): ?string
    {
        $topic = trim(preg_replace('/\s+/u', ' ', (string) $topic));
        if ($topic === '') {
            return null;
        }
        $slug = \Illuminate\Support\Str::slug($topic);
        foreach (static::topicsFor('sa-', $ignoreId) as $existing) {
            if (\Illuminate\Support\Str::slug($existing) === $slug) {
                return $existing;
            }
        }

        return mb_strtoupper(mb_substr($topic, 0, 1)) . mb_substr($topic, 1);
    }

    /** The topics already used by sample answers whose category starts with $prefix (e.g. "sa-ielts-speaking"). */
    public static function topicsFor(string $prefix, ?int $ignoreId = null): array
    {
        if (! static::hasTopicColumn()) {
            return [];
        }
        return static::where('category', 'like', $prefix . '%')->whereNotNull('topic')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->distinct()->orderBy('topic')->pluck('topic')->all();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Every category a post can be filed under, grouped for the admin dropdown:
     *   'Blogs'          => ielts, celpip, toefl, study-methods, language-learning
     *   'Sample Answers' => sa-ielts-speaking-part-1 … sa-celpip-writing-task-2
     * Each entry: ['label' => …, 'path' => page that lists the posts]
     */
    public static function categoryGroups(): array
    {
        $blogs = [];
        foreach (self::CATEGORIES as $key => $label) {
            $blogs[$key] = ['label' => $label, 'path' => 'blog/' . $key, 'title' => $label . ' Blog'];
        }

        return [
            'Blogs'          => $blogs,
            'Sample Answers' => \App\Support\SampleAnswers::categories(),
        ];
    }

    /** All categories in one list (key => info). */
    public static function allCategories(): array
    {
        return array_merge(...array_values(self::categoryGroups()));
    }

    public function isSampleAnswer(): bool
    {
        return str_starts_with((string) $this->category, 'sa-');
    }

    public function getCategoryLabelAttribute(): string
    {
        $info = self::allCategories()[$this->category] ?? null;
        if (! $info) return (string) $this->category;

        return $this->isSampleAnswer() ? 'Sample Answers › ' . $info['label'] : $info['label'];
    }

    /** Short name for the live website, e.g. "IELTS Speaking Part 1 Sample Answers" or "IELTS". */
    public function getCategoryTitleAttribute(): string
    {
        $info = self::allCategories()[$this->category] ?? null;

        return $info ? ($this->isSampleAnswer() ? $info['title'] : $info['label']) : (string) $this->category;
    }

    /** Address of the page that lists this category's posts. */
    public function getCategoryUrlAttribute(): string
    {
        $info = self::allCategories()[$this->category] ?? null;

        return url($info['path'] ?? 'blog/' . $this->category);
    }

    /**
     * Public address of the post on the live website:
     *   blog post:      /blog/ielts/my-post
     *   sample answer:  /sample-answers/ielts/speaking/part-1/my-post
     */
    public function getPublicPathAttribute(): string
    {
        $info = self::allCategories()[$this->category] ?? null;

        return '/' . ($info['path'] ?? 'blog/' . $this->category) . '/' . $this->slug;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Search-result title/description of one test's full mock page or one of its sections. */
class TestSeo extends Model
{
    protected $table = 'test_seo';

    protected $fillable = ['exam', 'test_id', 'page', 'meta_title', 'meta_description', 'indexable'];

    protected function casts(): array
    {
        return ['indexable' => 'boolean'];
    }

    /**
     * What the page of a test should say in Google.
     * $page = 'mock' (the full mock test) or a section name. Older meta fields are used when nothing was saved here.
     * Returns ['title', 'description', 'index'].
     */
    public static function resolve(string $exam, $test, string $page, string $defaultTitle): array
    {
        $row = static::where(['exam' => $exam, 'test_id' => $test->id, 'page' => $page])->first();
        $legacyTitle = $legacyDesc = null;
        if ($page === 'mock') {
            $legacyTitle = $test->meta_title;
            $legacyDesc = $test->meta_description;
        } elseif ($exam === 'toefl') {
            $m = $test->moduleRow($page);
            $legacyTitle = $m?->meta_title;
            $legacyDesc = $m?->meta_description;
        }

        return [
            'title'       => $row?->meta_title ?: ($legacyTitle ?: $defaultTitle),
            'description' => $row?->meta_description ?: $legacyDesc,
            'index'       => (bool) $row?->indexable,
        ];
    }

    /** The values to show in the admin boxes: [page => ['meta_title', 'meta_description', 'indexable']]. */
    public static function forAdmin(string $exam, $test, array $pages): array
    {
        $rows = static::where(['exam' => $exam, 'test_id' => $test->id])->get()->keyBy('page');
        $out = [];
        foreach ($pages as $page) {
            $r = $rows[$page] ?? null;
            $legacyTitle = $legacyDesc = null;
            if ($page === 'mock') {
                $legacyTitle = $test->meta_title;
                $legacyDesc = $test->meta_description;
            } elseif ($exam === 'toefl') {
                $m = $test->moduleRow($page);
                $legacyTitle = $m?->meta_title;
                $legacyDesc = $m?->meta_description;
            }
            $out[$page] = [
                'meta_title'       => $r?->meta_title ?: $legacyTitle,
                'meta_description' => $r?->meta_description ?: $legacyDesc,
                'indexable'        => (bool) $r?->indexable,
            ];
        }

        return $out;
    }
}

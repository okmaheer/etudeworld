<?php

namespace App\Support;

use App\Models\SeoPage;
use Illuminate\Support\Facades\Route;

/**
 * Every public page of the site that has a fixed address (home page, tools, vocabulary builder, level tests,
 * info pages, pages added in future …) so its search title and description can be edited in Admin → SEO Pages.
 * The list builds itself from the routes: a new page appears here without any extra work.
 * Not listed: blogs, individual tests, the exam pages (they have their own tabs), admin, login and similar.
 */
class SitePages
{
    /** The group of the saved rows in the seo_pages table. */
    public const GROUP = 'site';

    /** The sections of the home page whose heading and text can be edited. Saved as rows with the page "/#id". id => label. */
    public const HOME_PARTS = [
        'languages'    => 'Home – "Learn a New Language" section',
        'exams'        => 'Home – "Practice a Language Exam" section',
        'courses'      => 'Home – "Courses" section',
        'methods'      => 'Home – "Effective Study Methods" section',
        'achievements' => 'Home – "Our Achievements" section',
    ];

    /** Address starts that never belong in the list. */
    private const SKIP = ['admin', 'login', 'logout', 'my-portal', 'sitemap.xml', 'up', 'api', 'storage', 'livewire', 'sanctum', 'author', 'blog', 'password', 'register'];

    /** key => label. The order is the order of the dropdown. */
    public const SECTIONS = [
        'home'       => 'Home page',
        'languages'  => 'Languages',
        'courses'    => 'Courses',
        'tools'      => 'Tools',
        'vocabulary' => 'Vocabulary Builder',
        'level-test' => 'English level test',
        'samples'    => 'Sample answers',
        'info'       => 'About, contact & legal',
        'other'      => 'Other pages',
    ];

    /** Which section an address belongs to. */
    private static function sectionOf(string $path): string
    {
        return match (true) {
            $path === '/' => 'home',
            str_starts_with($path, '/languages') => 'languages',
            str_starts_with($path, '/courses') => 'courses',
            str_starts_with($path, '/tools') => 'tools',
            str_starts_with($path, '/vocabulary-builder') => 'vocabulary',
            str_starts_with($path, '/english-level-test') => 'level-test',
            str_contains($path, 'sample') => 'samples',
            in_array($path, ['/about-us', '/contact-us', '/privacy-policy', '/terms'], true) || preg_match('#^/(about|contact|privacy|terms)#', $path) === 1 => 'info',
            default => 'other',
        };
    }

    /** Addresses that are handled by the exam tabs, so they are not listed again. */
    private static function examPaths(): array
    {
        $paths = ['/ielts', '/celpip', '/toefl', '/toefl/practice-tests', '/toefl/mock-tests'];
        foreach (SeoPages::groups() as $key => $g) {
            foreach (array_merge(['hub', 'mock'], $g['modules']) as $page) {
                $paths[] = SeoPages::url($key, $page);
            }
        }

        return $paths;
    }

    /** All pages: [path => ['path','label','section']] sorted by address. */
    public static function all(): array
    {
        $skipExact = self::examPaths();
        $pages = [];
        foreach (Route::getRoutes() as $r) {
            if (! in_array('GET', $r->methods(), true) || str_contains($r->uri(), '{')) {
                continue;
            }
            $path = '/' . ltrim($r->uri(), '/');
            $first = explode('/', trim($path, '/'))[0];
            if (in_array($first, self::SKIP, true) || str_starts_with($first, '_') || in_array($path, $skipExact, true)) {
                continue;
            }
            // Redirects and file-like addresses are not pages
            if (isset($r->action['uses']) && is_string($r->action['uses']) && str_contains($r->action['uses'], 'RedirectController')) {
                continue;
            }
            if ($path === '/tools/study-planner/mine' || preg_match('/\.[a-z]{2,4}$/i', $path) || str_ends_with($path, '/data') || str_ends_with($path, '/print')) {
                continue;
            }
            if (($r->defaults['_redirect'] ?? null) || str_contains(implode(',', (array) ($r->action['middleware'] ?? [])), 'redirect')) {
                continue;
            }
            $pages[$path] = ['path' => $path, 'label' => self::label($path), 'section' => self::sectionOf($path)];
        }
        // Redirect routes (Route::redirect) point somewhere else – drop them.
        foreach (Route::getRoutes() as $r) {
            if ($r->getActionName() === 'Illuminate\Routing\RedirectController') {
                unset($pages['/' . ltrim($r->uri(), '/')]);
            }
        }
        // The sections of the home page (heading and text only)
        foreach (self::HOME_PARTS as $id => $label) {
            $pages['/#' . $id] = ['path' => '/#' . $id, 'label' => $label, 'section' => 'home', 'part' => $id];
        }
        // The language pages have the language in their address, so they are added by hand.
        foreach (\App\Models\LanguageModule::LANGUAGES as $key => $lang) {
            $pages['/languages/' . $key] = ['path' => '/languages/' . $key, 'label' => 'Learn ' . $lang['name'], 'section' => 'languages'];
        }
        ksort($pages);
        // On the home page tab: the page first, then its sections in the order they appear on the page.
        $order = array_merge(['/'], array_map(fn ($id) => '/#' . $id, array_keys(self::HOME_PARTS)));
        $home = [];
        foreach ($order as $k) {
            if (isset($pages[$k])) { $home[$k] = $pages[$k]; unset($pages[$k]); }
        }

        return $home + $pages;
    }

    /** A readable name from the address, e.g. /sample-answers/ielts/writing/task-1 → "IELTS – Writing – Task 1". */
    private static function label(string $path): string
    {
        if ($path === '/') {
            return 'Home page';
        }
        $parts = explode('/', trim($path, '/'));
        if (in_array($path, ['/vocabulary-builder/ielts', '/vocabulary-builder/celpip'], true)) {
            return strtoupper(basename($path)) . ' Vocabulary Builder home';
        }
        $roots = ['vocabulary-builder' => 'Vocabulary Builder home', 'tools' => 'Tools home', 'sample-answers' => 'Sample answers home'];
        if (count($parts) > 1 && array_key_exists($parts[0], $roots)) {
            array_shift($parts);
        } elseif (count($parts) === 1 && ! empty($roots[$parts[0]])) {
            return $roots[$parts[0]];
        }
        $acronyms = ['ielts', 'celpip', 'toefl', 'cefr', 'pte', 'hsk', 'tef', 'tcf', 'gt', 'ls'];
        $words = array_map(function ($seg) use ($acronyms) {
            return implode(' ', array_map(fn ($w) => in_array($w, $acronyms, true) ? strtoupper($w) : $w, explode('-', $seg)));
        }, $parts);
        $name = implode(' – ', $words);

        return ucfirst($name);
    }

    /** The pages of one section. */
    public static function section(string $section): array
    {
        return array_values(array_filter(self::all(), fn ($p) => $p['section'] === $section));
    }

    /** The sections that have at least one page, with the page count. */
    public static function sections(): array
    {
        $count = [];
        foreach (self::all() as $p) {
            $count[$p['section']] = ($count[$p['section']] ?? 0) + 1;
        }
        $out = [];
        foreach (self::SECTIONS as $key => $label) {
            if (! empty($count[$key])) {
                $out[$key] = ['label' => $label, 'count' => $count[$key]];
            }
        }

        return $out;
    }

    /** The saved override for the page being shown (null when none). Used by the site layout. */
    public static function override(string $path): ?SeoPage
    {
        $path = '/' . trim($path, '/');

        try {
            return SeoPage::where(['exam_group' => self::GROUP, 'page' => $path])->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Applies the heading, the text under it and the extra text saved in Admin → SEO Pages to a page.
     * The first <h1> gets the new heading, the paragraph right after it gets the new text, and the extra
     * text is added at the end of the page. Empty fields leave the page as it is.
     */
    public static function decorate(string $html, ?SeoPage $row): string
    {
        if ($html !== '' && request()->path() === '/') {
            $html = self::decorateHomeParts($html);
        }
        if (! $row || $html === '') {
            return $html;
        }
        if ($row->h1) {
            $html = preg_replace_callback('#(<h1\b[^>]*>)(.*?)(</h1>)#is', fn ($m) => $m[1] . e($row->h1) . $m[3], $html, 1);
        }
        if ($row->intro) {
            // the first paragraph that follows the heading
            $html = preg_replace_callback('#(</h1>\s*)<p\b([^>]*)>.*?</p>#is', fn ($m) => $m[1] . '<p' . $m[2] . '>' . e($row->intro) . '</p>', $html, 1);
        }
        if ($row->content) {
            $html .= '<section class="section section--compact seo-extra"><div class="container"><div class="article-body">' . $row->content . '</div></div></section>';
        }

        return $html;
    }

    /** Home page: the small label, heading and text of each section (saved in Admin → SEO Pages → Home page). */
    private static function decorateHomeParts(string $html): string
    {
        try {
            $rows = SeoPage::where('exam_group', self::GROUP)->where('page', 'like', '/#%')->get()->keyBy(fn ($r) => substr($r->page, 2));
        } catch (\Throwable) {
            return $html;
        }
        foreach ($rows as $id => $row) {
            if (! isset(self::HOME_PARTS[$id]) || ! ($row->meta_title || $row->h1 || $row->intro)) {
                continue;
            }
            $html = preg_replace_callback('#(<section\b[^>]*\bid="' . preg_quote($id, '#') . '"[^>]*>)(.*?)(</section>)#is', function ($m) use ($row) {
                $in = $m[2];
                if ($row->meta_title) {
                    $in = preg_replace_callback('#(<span class="eyebrow">)(.*?)(</span>)#is', fn ($x) => $x[1] . e($row->meta_title) . $x[3], $in, 1);
                }
                if ($row->h1) {
                    $in = preg_replace_callback('#(<h2\b[^>]*>)(.*?)(</h2>)#is', fn ($x) => $x[1] . e($row->h1) . $x[3], $in, 1);
                }
                if ($row->intro) {
                    $in = preg_replace_callback('#(</h2>\s*)<p\b([^>]*)>.*?</p>#is', fn ($x) => $x[1] . '<p' . $x[2] . '>' . e($row->intro) . '</p>', $in, 1);
                }

                return $m[1] . $in . $m[3];
            }, $html, 1);
        }

        return $html;
    }
}

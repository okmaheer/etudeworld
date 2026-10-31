<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Blog;
use App\Models\InfoPage;
use App\Models\LanguageLesson;
use App\Models\LanguageModule;
use App\Models\LevelTest;
use App\Support\SeoPages;
use App\Support\SitePages;

/**
 * /sitemap.xml – the list of public pages Google should know about.
 * Built from the same page list as Admin → SEO, plus the exam pages, blog posts, authors, language
 * courses and published info pages. "Coming soon" pages and unpublished pages are left out.
 */
class SitemapController extends Controller
{
    /** Placeholder addresses that only show "Coming soon" (see $upcoming in routes/web.php). */
    private const COMING_SOON = [];

    public function __invoke()
    {
        $urls = [];
        $add = function (string $path, $date = null) use (&$urls) {
            $loc = url($path === '/' ? '/' : '/' . ltrim($path, '/'));
            if (! isset($urls[$loc]) || $date) $urls[$loc] = $date ? $date->toAtomString() : null;
        };

        // Home, tools, vocabulary, sample answers, level test, languages …
        $info = InfoPage::all()->keyBy(fn ($p) => $p->public_path);
        foreach (SitePages::all() as $path => $page) {
            if (str_contains($path, '#') || in_array($path, self::COMING_SOON, true)) continue;
            if (! in_array($path, ['/about-us', '/contact-us'], true) && isset($info[$path]) && ! $info[$path]->is_live) continue;     // About / Contact … only once written
            $add($path);
        }

        // Exam pages: home pages, practice pages, mock-test lists and the list of each section
        foreach (SeoPages::landings() as $l) $add($l['path']);
        foreach (SeoPages::groups() as $key => $g) {
            foreach (array_merge(['hub', 'mock'], $g['modules']) as $page) $add(SeoPages::url($key, $page));
        }

        // Published info pages added in the admin panel (/page/…)
        foreach ($info as $p) {
            if ($p->is_live) $add($p->public_path, $p->updated_at);
        }

        // Language courses: modules and lessons that are published
        foreach (LanguageModule::published()->get() as $m) {
            if (! isset(LanguageModule::LANGUAGES[$m->language])) continue;
            $add(route('languages.module', [$m->language, $m->slug], false), $m->updated_at);
        }
        foreach (LanguageLesson::published()->with('module')->get() as $l) {
            $m = $l->module;
            if (! $m || ! $m->is_published || ! isset(LanguageModule::LANGUAGES[$m->language])) continue;
            $add(route('languages.lesson', [$m->language, $m->slug, $l->slug], false), $l->updated_at);
        }

        // Level tests, blog home, blog categories, posts and authors
        foreach (LevelTest::where('status', 'active')->get() as $t) {
            $add(route('level-test.show', $t->slug, false), $t->updated_at);
        }
        foreach (array_keys(Blog::CATEGORIES) as $cat) $add('/blog/' . $cat);
        foreach (Blog::published()->get() as $b) {
            $add($b->public_path, $b->updated_at);
        }
        foreach (Author::all() as $a) {
            if ($a->slug) $add(route('authors.show', $a->slug, false), $a->updated_at);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $loc => $date) {
            $xml .= '  <url><loc>' . e($loc) . '</loc>' . ($date ? '<lastmod>' . $date . '</lastmod>' : '') . '</url>' . "\n";
        }

        return response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}

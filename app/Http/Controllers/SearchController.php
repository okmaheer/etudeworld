<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Course;
use App\Models\LanguageLesson;
use App\Models\LanguageModule;
use App\Models\LevelTest;
use App\Support\SeoPages;
use App\Support\SitePages;
use Illuminate\Http\Request;

/** Site search: /search?q=... – looks through pages, tools, blog posts, sample answers, lessons and courses. */
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim(preg_replace('/\s+/', ' ', (string) $request->query('q', '')));
        $q = mb_substr($q, 0, 60);
        $results = [];

        if (mb_strlen($q) >= 2) {
            $words = array_values(array_unique(array_filter(array_map(
                fn ($w) => (mb_strlen($w) > 3 && str_ends_with($w, 's')) ? mb_substr($w, 0, -1) : $w,   // "tests" → "test"
                explode(' ', mb_strtolower($q))
            ), fn ($w) => mb_strlen($w) >= 2)));
            $add = function (string $type, string $title, ?string $text, string $url, int $boost = 0) use (&$results, $words, $q) {
                $score = $this->score($words, $q, $title, (string) $text);
                if ($score > 0) {
                    $score += $boost;
                    $results[] = ['type' => $type, 'title' => $title, 'text' => $this->snippet((string) $text, $words), 'url' => $url, 'score' => $score];
                }
            };

            // Main pages (the same list as Admin → SEO Pages) and the exam pages
            foreach (SitePages::all() as $path => $page) {
                if (str_contains($path, '#')) continue;
                $add('Page', $this->tidy($page['label']), str_replace(['/', '-'], ' ', $path), url($path));
            }
            foreach (SeoPages::landings() as $l) {
                $name = strtoupper(trim(str_replace('home page', '', $l['label'])));
                $add('Practice test', $name, $name . ' practice tests mock tests free online', url($l['path']), 20);
            }
            foreach (SeoPages::groups() as $key => $g) {
                $add('Practice test', $g['label'] . ' – Practice tests', $g['label'] . ' practice tests free online listening reading writing speaking', url(SeoPages::url($key, 'hub')));
                $add('Practice test', $g['label'] . ' – Full mock tests', $g['label'] . ' full mock test computer based', url(SeoPages::url($key, 'mock')));
                foreach ($g['modules'] as $m) {
                    $add('Practice test', $g['label'] . ' – ' . ucfirst($m) . ' practice tests', $g['label'] . ' ' . $m . ' practice test', url(SeoPages::url($key, $m)));
                }
            }

            // Tools
            foreach (ToolsController::PAGES as $name => $p) {
                $add('Tool', $p['title'], ($p['blurb'] ?? '') . ' ' . ($p['desc'] ?? ''), route('tools.' . $name));
            }

            // Courses (paid)
            foreach (Course::where('is_published', true)->get() as $c) {
                $add('Course', $c->title, $c->summary, route('courses.index') . '#' . $c->slug);
            }

            // Blog posts and sample answers (database search first, then scoring)
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $words[0] ?? $q) . '%';
            $blogs = Blog::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('meta_description', 'like', $like)->orWhere('content', 'like', $like))->limit(60)->get();
            foreach ($blogs as $b) {
                $label = (Blog::allCategories()[$b->category]['label'] ?? 'Blog');
                $add($label === 'Blog' ? 'Blog' : $label, $b->title, $b->meta_description ?: strip_tags((string) $b->content), url($b->public_path));
            }

            // Language lessons
            foreach (LanguageModule::published()->get() as $m) {
                if (! isset(LanguageModule::LANGUAGES[$m->language])) continue;
                $add('Language course', LanguageModule::LANGUAGES[$m->language]['name'] . ' – ' . $m->title, $m->description, route('languages.module', [$m->language, $m->slug]));
            }
            foreach (LanguageLesson::published()->with('module')->get() as $l) {
                $m = $l->module;
                if (! $m || ! $m->is_published || ! isset(LanguageModule::LANGUAGES[$m->language])) continue;
                $add('Lesson', LanguageModule::LANGUAGES[$m->language]['name'] . ' – ' . $l->title, $l->summary ?: strip_tags((string) $l->body), route('languages.lesson', [$m->language, $m->slug, $l->slug]));
            }

            foreach (LevelTest::where('status', 'active')->get() as $t) {
                $add('Test', $t->name ?? $t->title ?? 'English level test', null, route('level-test.show', $t->slug));
            }

            // Same page found twice? keep the best one
            $best = [];
            foreach ($results as $r) {
                if (! isset($best[$r['url']]) || $r['score'] > $best[$r['url']]['score']) $best[$r['url']] = $r;
            }
            $results = collect($best)->sortByDesc('score')->values()->take(40)->all();
        }

        return response()
            ->view('search', ['q' => $q, 'results' => $results, 'tooShort' => $q !== '' && mb_strlen($q) < 2])
            ->header('X-Robots-Tag', 'noindex, follow');
    }

    /** Words too common to decide a match on their own ("ielts practice test": only "ielts" has to be found). */
    private const COMMON = ['practice', 'test', 'free', 'online', 'the', 'for', 'and', 'how', 'to', 'of', 'a', 'in', 'my', 'exam'];

    /**
     * Title hits count most, then the short description, then the long text.
     * At least one real word must be found; the more of the typed words are found, the higher the result.
     */
    private function score(array $words, string $q, string $title, string $text): int
    {
        if (! $words) return 0;
        $t = mb_strtolower($title);
        $d = mb_strtolower(strip_tags($text));
        $key = array_values(array_filter($words, fn ($w) => ! in_array($w, self::COMMON, true)));
        $need = $key ?: $words;
        $found = 0;
        foreach ($need as $w) {
            if (str_contains($t, $w) || str_contains($d, $w)) $found++;
        }
        if ($found === 0 || ($found < count($need) && count($need) <= 2)) return 0;   // one or two key words: all must be found
        if ($found < count($need) && $found * 2 < count($need)) return 0;             // longer search: at least half

        $score = 0;
        foreach ($words as $w) {
            if (str_contains($t, $w)) $score += in_array($w, self::COMMON, true) ? 3 : 10;
            elseif (str_contains($d, $w)) $score += in_array($w, self::COMMON, true) ? 1 : 2;
        }
        if (str_contains($t, mb_strtolower($q))) $score += 15;
        if (str_starts_with($t, mb_strtolower($q))) $score += 10;

        return $score;
    }

    /** "IELTS – speaking – part 1" → "IELTS – Speaking – Part 1" */
    private function tidy(string $label): string
    {
        return implode(' – ', array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1), explode(' – ', $label)));
    }

    /** A short piece of text around the first matching word. */
    private function snippet(string $text, array $words): string
    {
        $text = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text))));
        if ($text === '') return '';
        $pos = 0;
        foreach ($words as $w) {
            $p = mb_stripos($text, $w);
            if ($p !== false) { $pos = $p; break; }
        }
        $start = max(0, $pos - 50);

        return ($start > 0 ? '…' : '') . mb_substr($text, $start, 160) . (mb_strlen($text) > $start + 160 ? '…' : '');
    }
}

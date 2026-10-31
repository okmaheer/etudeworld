<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogPageController extends Controller
{
    /** A sample answer: /sample-answers/ielts/speaking/part-1/{slug} (the category is set by the route). */
    public function showSample(string $slug, string $category)
    {
        return $this->show($category, $slug);
    }

    /** /blog/ielts, /blog/celpip, /blog/toefl, /blog/study-methods, /blog/language-learning */
    public function category(string $category)
    {
        $info = Blog::CATEGORIES[$category] ?? abort(404);

        $posts = Blog::with('author')->published()->where('category', $category)
            ->latest('published_at')->paginate(12);

        return view('blog.index', ['posts' => $posts, 'current' => $category, 'info' => $info]);
    }

    /** A single blog post on the live website: /blog/{category}/{slug} */
    public function show(string $category, string $slug)
    {
        $blog = Blog::with('author')
            ->where('category', $category)
            ->where('slug', $slug)
            // Drafts are hidden from visitors, but a logged-in admin can preview them.
            ->when(! Auth::check(), fn ($q) => $q->published())
            ->firstOrFail();

        [$content, $toc] = $this->addHeadingIds($blog->content ?? '');
        $plain = trim(strip_tags($blog->content ?? ''));
        $words = $plain === '' ? 0 : str_word_count($plain);

        return view('blog.show', [
            'blog'        => $blog,
            'content'     => $content,
            'toc'         => $toc,
            'readingTime' => max(1, (int) ceil($words / 220)),
            'recent'      => Blog::published()->where('category', $blog->category)->latest('published_at')->latest('id')->get(['id', 'title', 'slug', 'category', 'published_at']),
            'recentLabel' => trim(preg_replace('/\s*Sample Answers$/', '', $blog->category_title)),
            'faqs'        => \App\Support\Faq::extract($blog->content ?? ''),
            'canonical'   => $blog->canonical_url ?: url($blog->public_path),
            'description' => $blog->meta_description ?: Str::limit($plain, 155),
        ]);
    }

    /**
     * Gives every <h2> and <h3> an id (made from its text) and returns the list for the
     * "Table of Contents" box. A heading that already has an id keeps it.
     * Returns [html, [['id' => …, 'text' => …, 'level' => 2|3], …]]
     */
    private function addHeadingIds(string $html): array
    {
        $toc = [];
        $used = [];
        $html = preg_replace_callback('/<h([23])(\s[^>]*)?>(.*?)<\/h\1>/is', function ($m) use (&$toc, &$used) {
            $attrs = $m[2] ?? '';
            $text = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $text = preg_replace('/\s+/u', ' ', $text);
            if ($text === '') {
                return $m[0];
            }
            if (preg_match('/\sid\s*=\s*(["\'])(.*?)\1/i', $attrs, $im)) {
                $id = $im[2];
            } else {
                $base = Str::slug($text) ?: 'section';
                $id = $base;
                for ($n = 2; isset($used[$id]); $n++) {
                    $id = $base . '-' . $n;
                }
                $attrs .= ' id="' . $id . '"';
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $m[1]];

            return '<h' . $m[1] . $attrs . '>' . $m[3] . '</h' . $m[1] . '>';
        }, $html);

        return [$html, $toc];
    }
}

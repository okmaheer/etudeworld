<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Support\SampleAnswers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SampleAnswersController extends Controller
{
    /** /sample-answers/ielts/speaking/part-1 – all sample answers in one section. */
    public function category(string $key)
    {
        $cat = SampleAnswers::categories()[$key] ?? abort(404);

        $posts = Blog::with('author')->published()->where('category', $key)
            ->latest('published_at')->paginate(12);

        return view('sample-answers.category', compact('key', 'cat', 'posts'));
    }

    /**
     * /sample-answers/ielts/speaking – every sample answer of one exam skill.
     * Left: topics (Transport, Health …). Right: Part / Task buttons and the answers.
     * Filters come from the address: ?topic=transport&part=part-1
     */
    public function hub(Request $request, string $hub)
    {
        $h = SampleAnswers::hubs()[$hub] ?? abort(404);
        $keys = array_column($h['items'], 'key');
        $items = collect($h['items'])->keyBy(fn ($i) => basename($i['path']));       // part-1 => [...]

        $part = $items->has($request->query('part')) ? $request->query('part') : null;
        $topic = $request->query('topic') ? Str::slug((string) $request->query('topic')) : null;

        $hasTopic = Blog::hasTopicColumn();
        $rows = Blog::published()->whereIn('category', $keys)->get($hasTopic ? ['id', 'category', 'topic'] : ['id', 'category']);
        $slugOf = fn ($t) => $t ? Str::slug($t) : 'other';
        $inPart = fn ($r) => ! $part || $r->category === $items[$part]['key'];
        $inTopic = fn ($r) => ! $topic || $slugOf($r->topic) === $topic;

        // Topic list (counted inside the chosen Part) and Part buttons (counted inside the chosen topic)
        $topics = [];
        foreach ($rows->filter($inPart) as $r) {
            $k = $slugOf($r->topic);
            $topics[$k] ??= ['slug' => $k, 'label' => $r->topic ?: 'Other', 'count' => 0];
            $topics[$k]['count']++;
        }
        uasort($topics, fn ($a, $b) => ($a['slug'] === 'other') <=> ($b['slug'] === 'other') ?: strcasecmp($a['label'], $b['label']));
        $partCounts = [];
        foreach ($rows->filter($inTopic) as $r) {
            $partCounts[$r->category] = ($partCounts[$r->category] ?? 0) + 1;
        }

        $posts = Blog::with('author')->published()
            ->whereIn('category', $part ? [$items[$part]['key']] : $keys)
            ->when($hasTopic && $topic === 'other', fn ($q) => $q->whereNull('topic'))
            ->when($topic && $topic !== 'other', fn ($q) => $q->whereIn('id', $rows->filter(fn ($r) => $slugOf($r->topic) === $topic)->pluck('id')))
            ->latest('published_at')->paginate(12)->withQueryString();

        return view('sample-answers.hub', [
            'h' => $h, 'posts' => $posts, 'topics' => $topics, 'partCounts' => $partCounts,
            'part' => $part, 'topic' => $topic, 'total' => $rows->count(),
            'topicLabel' => $topic ? ($topics[$topic]['label'] ?? null) : null,
        ]);
    }
}

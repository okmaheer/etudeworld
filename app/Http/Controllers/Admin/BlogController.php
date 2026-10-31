<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    private const UPLOAD_DIR = 'uploads/blogs';

    public function index(Request $request)
    {
        $blogs = Blog::with('author')
            ->when($request->category === 'sample-answers', fn ($q) => $q->where('category', 'like', 'sa-%'))
            ->when($request->category === 'blogs', fn ($q) => $q->where('category', 'not like', 'sa-%'))
            ->when($request->filled('category') && ! in_array($request->category, ['blogs', 'sample-answers'], true),
                fn ($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blogs.form', [
            'blog'    => new Blog(['status' => 'draft']),
            'authors' => Author::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image'] = $this->saveImage($request);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $blog = Blog::create($data);

        return redirect()->route('admin.blogs.edit', $blog)
            ->with('success', 'Blog "' . $blog->title . '" has been saved.');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.form', [
            'blog'    => $blog,
            'authors' => Author::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validated($request, $blog);

        if ($request->hasFile('image')) {
            $this->deleteImage($blog->image);
            $data['image'] = $this->saveImage($request);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($blog->image);
            $data['image'] = null;
        }

        if ($data['status'] === 'published' && ! $blog->published_at) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return redirect()->route('admin.blogs.edit', $blog)
            ->with('success', 'Changes saved.');
    }

    public function destroy(Blog $blog)
    {
        $this->deleteImage($blog->image);
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog "' . $blog->title . '" was deleted.');
    }

    /** Images pasted/inserted inside the blog text editor. */
    public function uploadEditorImage(Request $request)
    {
        $request->validate(['file' => ['required', 'image', 'max:4096']]);

        $file = $request->file('file');
        $name = Str::random(20) . '.' . $file->extension();
        $file->move(public_path(self::UPLOAD_DIR . '/content'), $name);

        return response()->json(['location' => asset(self::UPLOAD_DIR . '/content/' . $name)]);
    }

    // ───────────────────────── helpers ─────────────────────────

    private function validated(Request $request, ?Blog $blog = null): array
    {
        // Turn whatever is typed in the URL box into a clean web address.
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('title'))]);

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', Rule::unique('blogs', 'slug')->ignore($blog?->id)],
            'category'         => ['required', Rule::in(array_keys(Blog::allCategories()))],
            'author_id'        => ['nullable', 'integer', 'exists:authors,id'],
            'content'          => ['nullable', 'string'],
            'meta_title'       => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_url'    => ['nullable', 'url', 'max:255'],
            'image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'image_alt'        => ['nullable', 'string', 'max:255'],
            'topic'            => ['nullable', 'string', 'max:80'],
            'status'           => ['required', Rule::in(array_keys(Blog::STATUSES))],
        ], [
            'slug.unique' => 'Another blog already uses this URL. Please choose a different one.',
            'image.max'   => 'The image must be smaller than 4 MB.',
        ]);

        // The topic only applies to sample answers
        if (Blog::hasTopicColumn()) {
            $data['topic'] = Blog::isSampleCategory($data['category']) ? Blog::cleanTopic($data['topic'] ?? null, $blog?->id) : null;
        } else {
            unset($data['topic']);
        }

        return $data;
    }

    private function saveImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $name = Str::slug($request->input('slug')) . '-' . Str::random(6) . '.' . $file->extension();
        $file->move(public_path(self::UPLOAD_DIR), $name);

        return self::UPLOAD_DIR . '/' . $name;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, self::UPLOAD_DIR)) {
            File::delete(public_path($path));
        }
    }
}

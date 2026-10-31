<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AuthorController extends Controller
{
    private const UPLOAD_DIR = 'uploads/authors';

    /** All authors */
    public function index()
    {
        $authors = Author::withCount('blogs')->orderBy('name')->get();

        return view('admin.authors.index', compact('authors'));
    }

    /** Add new author */
    public function create()
    {
        return view('admin.authors.form', ['author' => new Author]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['image'] = $this->saveImage($request, $data['slug']);

        $author = Author::create($data);

        return redirect()->route('admin.authors.index')
            ->with('success', 'Author "' . $author->name . '" was added.');
    }

    public function edit(Author $author)
    {
        return view('admin.authors.form', compact('author'));
    }

    public function update(Request $request, Author $author)
    {
        $data = $this->validated($request);

        if ($data['name'] !== $author->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $author->id);
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($author->image);
            $data['image'] = $this->saveImage($request, $data['slug'] ?? $author->slug);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($author->image);
            $data['image'] = null;
        }

        $author->update($data);

        return redirect()->route('admin.authors.index')
            ->with('success', 'Author "' . $author->name . '" was updated.');
    }

    public function destroy(Author $author)
    {
        // Blogs written by this author are kept; they simply show no author.
        $this->deleteImage($author->image);
        $author->delete();

        return redirect()->route('admin.authors.index')
            ->with('success', 'Author "' . $author->name . '" was deleted.');
    }

    // ───────────────────────── helpers ─────────────────────────

    private function validated(Request $request): array
    {
        // Allow typing links without "https://"
        foreach (array_keys(Author::SOCIALS) as $key) {
            $value = trim((string) $request->input($key));
            if ($value !== '' && ! preg_match('#^https?://#i', $value)) {
                $request->merge([$key => 'https://' . $value]);
            }
        }

        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'bio'      => ['nullable', 'string'],
            'image'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'x'        => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
        ], [
            'image.max' => 'The image must be smaller than 4 MB.',
            '*.url'     => 'Please enter a full web address, e.g. https://www.linkedin.com/in/your-name',
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'author';
        $slug = $base;
        $i = 2;
        while (Author::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function saveImage(Request $request, string $slug): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $name = $slug . '-' . Str::random(6) . '.' . $file->extension();
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

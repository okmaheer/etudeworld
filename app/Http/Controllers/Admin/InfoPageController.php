<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InfoPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InfoPageController extends Controller
{
    public function index()
    {
        $pages = InfoPage::orderByDesc('is_system')->orderBy('id')->get();

        return view('admin.info-pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.info-pages.form', ['page' => new InfoPage(['is_published' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $page = InfoPage::create($data);

        return redirect()->route('admin.info-pages.index')->with('success', 'Page "' . $page->title . '" was added.');
    }

    public function edit(InfoPage $page)
    {
        return view('admin.info-pages.form', compact('page'));
    }

    public function update(Request $request, InfoPage $page)
    {
        $data = $this->validated($request);
        if ($page->is_system) {
            unset($data['slug']);
        } else {
            $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $page->id);
        }
        $page->update($data);

        return redirect()->route('admin.info-pages.index')->with('success', 'Page "' . $page->title . '" was saved.');
    }

    public function destroy(InfoPage $page)
    {
        abort_if($page->is_system, 403);
        $page->delete();

        return redirect()->route('admin.info-pages.index')->with('success', 'Page "' . $page->title . '" was deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:120'],
            'body'             => ['nullable', 'string', 'max:500000'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'page';
        $slug = $base;
        $i = 2;
        while (InfoPage::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}

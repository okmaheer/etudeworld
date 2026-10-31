<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AchievementController extends Controller
{
    public function index()
    {
        $items = Achievement::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.achievements.index', compact('items'));
    }

    public function create()
    {
        return view('admin.achievements.form', ['item' => new Achievement(['is_published' => true, 'sort_order' => Achievement::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['photo'] = $this->savePhoto($request, $data['name']);
        $item = Achievement::create($data);

        return redirect()->route('admin.achievements.index')->with('success', 'Story of ' . $item->name . ' was added.');
    }

    public function edit(Achievement $achievement)
    {
        return view('admin.achievements.form', ['item' => $achievement]);
    }

    public function update(Request $request, Achievement $achievement)
    {
        $data = $this->validated($request);
        if ($request->hasFile('photo')) {
            $this->deletePhoto($achievement->photo);
            $data['photo'] = $this->savePhoto($request, $data['name']);
        } elseif ($request->boolean('remove_photo')) {
            $this->deletePhoto($achievement->photo);
            $data['photo'] = null;
        }
        $achievement->update($data);

        return redirect()->route('admin.achievements.index')->with('success', 'Story of ' . $achievement->name . ' was saved.');
    }

    public function destroy(Achievement $achievement)
    {
        $this->deletePhoto($achievement->photo);
        $achievement->delete();

        return redirect()->route('admin.achievements.index')->with('success', 'Story of ' . $achievement->name . ' was deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:120'],
            'exam'       => ['nullable', 'string', 'max:120'],
            'score'      => ['nullable', 'string', 'max:60'],
            'quote'      => ['nullable', 'string', 'max:600'],
            'photo'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], ['photo.max' => 'The photo must be smaller than 4 MB.']);
        unset($data['photo']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function savePhoto(Request $request, string $name): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }
        $file = $request->file('photo');
        $fileName = (Str::slug($name) ?: 'student') . '-' . Str::random(6) . '.' . $file->extension();
        $file->move(public_path(Achievement::UPLOAD_DIR), $fileName);

        return Achievement::UPLOAD_DIR . '/' . $fileName;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, Achievement::UPLOAD_DIR)) {
            File::delete(public_path($path));
        }
    }
}

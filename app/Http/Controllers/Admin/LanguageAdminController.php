<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LanguageLesson;
use App\Models\LanguageModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LanguageAdminController extends Controller
{
    // ───────────── Modules ─────────────

    public function index(string $language)
    {
        $info = $this->info($language);
        $modules = LanguageModule::forLanguage($language)->withCount('lessons')->get();

        return view('admin.languages.modules', compact('language', 'info', 'modules'));
    }

    public function createModule(string $language)
    {
        $info = $this->info($language);

        return view('admin.languages.module-form', ['language' => $language, 'info' => $info, 'module' => new LanguageModule(['is_published' => true, 'sort_order' => LanguageModule::where('language', $language)->max('sort_order') + 1])]);
    }

    public function storeModule(Request $request, string $language)
    {
        $this->info($language);
        $data = $this->moduleData($request);
        $data['language'] = $language;
        $data['slug'] = $this->slug(LanguageModule::where('language', $language), $data['title']);
        LanguageModule::create($data);

        return redirect()->route('admin.languages.index', $language)->with('success', 'Module "' . $data['title'] . '" was added.');
    }

    public function editModule(string $language, LanguageModule $module)
    {
        $info = $this->info($language);

        return view('admin.languages.module-form', compact('language', 'info', 'module'));
    }

    public function updateModule(Request $request, string $language, LanguageModule $module)
    {
        $data = $this->moduleData($request);
        if ($data['title'] !== $module->title) {
            $data['slug'] = $this->slug(LanguageModule::where('language', $language), $data['title'], $module->id);
        }
        $module->update($data);

        return redirect()->route('admin.languages.index', $language)->with('success', 'Module "' . $module->title . '" was updated.');
    }

    public function destroyModule(string $language, LanguageModule $module)
    {
        $module->delete(); // its lessons go with it

        return redirect()->route('admin.languages.index', $language)->with('success', 'Module "' . $module->title . '" and its lessons were deleted.');
    }

    // ───────────── Lessons ─────────────

    public function lessons(string $language, LanguageModule $module)
    {
        $info = $this->info($language);
        $lessons = $module->lessons()->get();

        return view('admin.languages.lessons', compact('language', 'info', 'module', 'lessons'));
    }

    public function createLesson(string $language, LanguageModule $module)
    {
        $info = $this->info($language);
        $lesson = new LanguageLesson(['is_published' => true, 'sort_order' => $module->lessons()->max('sort_order') + 1]);

        return view('admin.languages.lesson-form', compact('language', 'info', 'module', 'lesson'));
    }

    public function storeLesson(Request $request, string $language, LanguageModule $module)
    {
        $data = $this->lessonData($request);
        $data['slug'] = $this->slug($module->lessons()->getQuery(), $data['title']);
        $module->lessons()->create($data);

        return redirect()->route('admin.languages.lessons', [$language, $module])->with('success', 'Lesson "' . $data['title'] . '" was added.');
    }

    public function editLesson(string $language, LanguageModule $module, LanguageLesson $lesson)
    {
        $info = $this->info($language);
        abort_unless($lesson->language_module_id === $module->id, 404);

        return view('admin.languages.lesson-form', compact('language', 'info', 'module', 'lesson'));
    }

    public function updateLesson(Request $request, string $language, LanguageModule $module, LanguageLesson $lesson)
    {
        abort_unless($lesson->language_module_id === $module->id, 404);
        $data = $this->lessonData($request);
        if ($data['title'] !== $lesson->title) {
            $data['slug'] = $this->slug($module->lessons()->getQuery(), $data['title'], $lesson->id);
        }
        $lesson->update($data);

        return redirect()->route('admin.languages.lessons', [$language, $module])->with('success', 'Lesson "' . $lesson->title . '" was updated.');
    }

    public function destroyLesson(string $language, LanguageModule $module, LanguageLesson $lesson)
    {
        abort_unless($lesson->language_module_id === $module->id, 404);
        $lesson->delete();

        return redirect()->route('admin.languages.lessons', [$language, $module])->with('success', 'Lesson "' . $lesson->title . '" was deleted.');
    }

    // ───────────── helpers ─────────────

    private function info(string $language): array
    {
        return LanguageModule::LANGUAGES[$language] ?? abort(404);
    }

    private function moduleData(Request $request): array
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:120'],
            'icon'        => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:400'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function lessonData(Request $request): array
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:160'],
            'level'      => ['nullable', 'in:' . implode(',', array_keys(LanguageLesson::LEVELS))],
            'summary'    => ['nullable', 'string', 'max:400'],
            'body'       => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function slug($query, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'item';
        $slug = $base;
        $i = 2;
        while ((clone $query)->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}

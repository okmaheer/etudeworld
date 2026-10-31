<?php

namespace App\Http\Controllers;

use App\Models\LanguageLesson;
use App\Models\LanguageModule;

class LanguageController extends Controller
{
    /** /languages/german : all modules */
    public function show(string $language)
    {
        $info = $this->info($language);
        $modules = LanguageModule::forLanguage($language)->published()
            ->withCount(['lessons as lessons_count' => fn ($q) => $q->where('is_published', true)])
            ->get();

        return view('languages.show', compact('language', 'info', 'modules'));
    }

    /** /languages/german/grammar : lessons in one module */
    public function module(string $language, string $module)
    {
        $info = $this->info($language);
        $module = LanguageModule::forLanguage($language)->published()->where('slug', $module)->firstOrFail();
        $lessons = $module->lessons()->published()->get();

        return view('languages.module', compact('language', 'info', 'module', 'lessons'));
    }

    /** /languages/german/grammar/the-cases : one lesson */
    public function lesson(string $language, string $module, string $lesson)
    {
        $info = $this->info($language);
        $module = LanguageModule::forLanguage($language)->published()->where('slug', $module)->firstOrFail();
        $all = $module->lessons()->published()->get();
        $lesson = $all->firstWhere('slug', $lesson);
        abort_unless($lesson, 404);

        $i = $all->search(fn ($l) => $l->id === $lesson->id);
        $prev = $all->get($i - 1) && $i > 0 ? $all->get($i - 1) : null;
        $next = $all->get($i + 1);

        return view('languages.lesson', compact('language', 'info', 'module', 'lesson', 'prev', 'next'));
    }

    private function info(string $language): array
    {
        return LanguageModule::LANGUAGES[$language] ?? abort(404);
    }
}

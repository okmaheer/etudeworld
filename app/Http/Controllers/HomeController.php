<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        // ── Section 1: Learn a New Language ──────────────────────────────
        $languages = collect(\App\Models\LanguageModule::LANGUAGES)->map(fn ($l, $key) => [
            'flag' => $l['flag'], 'name' => $l['name'], 'text' => $l['tagline'], 'url' => route('languages.show', $key),
        ])->values()->all();

        // ── Section 3: Effective Study Methods ───────────────────────────
        $home = \App\Models\SiteSetting::group('home');
        $methods = $home['methods'];
        $hero = $home['hero'];
        $exams = $home['exams'];
        $level = $home['level'];

        // ── Section 4: Our Achievements (slideshow) – student stories written in Admin → Achievements ──
        $achievements = \App\Models\Achievement::where('is_published', true)->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($a) => ['name' => $a->name, 'exam' => (string) $a->exam, 'score' => (string) $a->score, 'photo' => $a->photo ?: 'images/student-1.svg', 'text' => (string) $a->quote])
            ->all();

        return view('home', compact('languages', 'methods', 'achievements', 'hero', 'exams', 'level'));
    }
}

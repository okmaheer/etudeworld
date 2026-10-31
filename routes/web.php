<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CelpipTestController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthorPageController;
use App\Http\Controllers\BlogPageController;
use App\Http\Controllers\CelpipPracticeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SampleAnswersController;
use Illuminate\Support\Facades\Route;

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Pages that are not built yet
|--------------------------------------------------------------------------
| Every link in the menu and footer points to a real address, so nothing
| shows a "404 Not Found" error. Until each page is built, it shows a
| friendly "Coming soon" screen. To build a page later, replace its line
| with a proper controller/view.
*/
$upcoming = [


    // Blogs

    // Homepage sections

    // Footer – company



    // Vocabulary Builder (menu pages still to be built)

    // Tools (pages still to be built)
];

// Courses (paid): cards for IELTS / CELPIP / TOEFL, written in Admin → Courses
Route::get('courses', [\App\Http\Controllers\CoursesController::class, 'index'])->name('courses.index');
Route::post('courses/request', [\App\Http\Controllers\CoursesController::class, 'requestQuote'])->middleware('throttle:5,10')->name('courses.request');

// Tools (calculators and converters; they run in the visitor's browser)
Route::get('tools/study-planner/mine', [\App\Http\Controllers\StudyPlannerController::class, 'mine'])->name('tools.study-planner.mine');
Route::post('tools/study-planner/plan', [\App\Http\Controllers\StudyPlannerController::class, 'plan'])->middleware('throttle:6,60')->name('tools.study-planner.plan');
foreach (\App\Http\Controllers\ToolsController::PAGES as $name => $page) {
    Route::get('tools/' . $page['uri'], [\App\Http\Controllers\ToolsController::class, 'show'])
        ->defaults('tool', $name)->name('tools.' . $name);
}

// Language learning (German first): /languages/german, /languages/german/grammar, /languages/german/grammar/the-cases
Route::prefix('languages/{language}')->whereIn('language', array_keys(\App\Models\LanguageModule::LANGUAGES))->group(function () {
    Route::get('/', [\App\Http\Controllers\LanguageController::class, 'show'])->name('languages.show');
    Route::get('{module}', [\App\Http\Controllers\LanguageController::class, 'module'])->name('languages.module');
    Route::get('{module}/{lesson}', [\App\Http\Controllers\LanguageController::class, 'lesson'])->name('languages.lesson');
});

Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->middleware('throttle:60,1')->name('search');
Route::get('sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

// English Level Test (public)
Route::get('english-level-test', [\App\Http\Controllers\LevelTestController::class, 'index'])->name('level-test.index');
Route::get('english-level-test/{slug}', [\App\Http\Controllers\LevelTestController::class, 'show'])->name('level-test.show');
Route::post('english-level-test/{slug}/score', [\App\Http\Controllers\LevelTestController::class, 'score'])->name('level-test.score');
Route::redirect('tools/english-level-test', '/english-level-test')->name('tools.cefr-test');

// Vocabulary Builder (students)
// The old Vocabulary Builder home page was removed; old links go to the IELTS vocabulary page.
Route::redirect('vocabulary-builder', '/vocabulary-builder/ielts', 301)->name('vocab.hub');
Route::controller(\App\Http\Controllers\VocabBuilderController::class)->prefix('vocabulary-builder')->group(function () {
    Route::get('ielts/listening-spellings', 'spellings')->name('vocab.ielts.spellings');
    foreach (['ielts', 'celpip'] as $exam) {
        Route::get($exam, 'exam')->defaults('exam', $exam)->name('vocab.' . $exam);
        foreach (['listening', 'reading'] as $module) {
            Route::get("$exam/$module", 'skill')->defaults('exam', $exam)->defaults('module', $module)->name("vocab.$exam.$module");
        }
        foreach (['writing', 'speaking'] as $skill) {
            Route::get("$exam/$skill", 'topics')->defaults('exam', $exam)->defaults('skill', $skill)->name("vocab.$exam.$skill");
        }
    }
    // One IELTS / CELPIP test section's words
    Route::get('ielts/{module}/{test}', 'show')->defaults('kind', 'ielts')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->name('vocab.test');
    Route::get('ielts/{module}/{test}/exercises', 'exercises')->defaults('kind', 'ielts')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->name('vocab.exercises');
    Route::post('ielts/{module}/{test}/progress', 'saveProgress')->defaults('kind', 'ielts')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->middleware('throttle:60,1')->name('vocab.progress');
    Route::get('celpip/{module}/{test}', 'show')->defaults('kind', 'celpip')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->name('vocab.celpip.test');
    Route::get('celpip/{module}/{test}/exercises', 'exercises')->defaults('kind', 'celpip')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->name('vocab.celpip.exercises');
    Route::get('celpip/{module}/{test}/data', 'data')->defaults('kind', 'celpip')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->name('vocab.celpip.data');
    Route::post('celpip/{module}/{test}/progress', 'saveProgress')->defaults('kind', 'celpip')->whereIn('module', ['listening', 'reading'])->whereNumber('test')->middleware('throttle:60,1')->name('vocab.celpip.progress');
    // One Writing / Speaking topic's words
    Route::prefix('{exam}/{skill}/{slug}')->whereIn('exam', ['ielts', 'celpip'])->whereIn('skill', ['writing', 'speaking'])->group(function () {
        Route::get('/', 'show')->defaults('kind', 'topic')->name('vocab.topic');
        Route::get('exercises', 'exercises')->defaults('kind', 'topic')->name('vocab.topic.exercises');
        Route::get('data', 'data')->defaults('kind', 'topic')->name('vocab.topic.data');
        Route::post('progress', 'saveProgress')->defaults('kind', 'topic')->middleware('throttle:60,1')->name('vocab.topic.progress');
    });
});

// Info pages (About, Contact, Privacy, Terms) are written in Admin → Info Pages
// About and Contact have their own designed pages; their extra text, if any, is still written in Admin → Pages
Route::get('about-us', [\App\Http\Controllers\AboutController::class, 'show'])->name('about');
Route::get('contact-us', [\App\Http\Controllers\ContactController::class, 'show'])->name('contact');
Route::post('contact-us', [\App\Http\Controllers\ContactController::class, 'send'])->middleware('throttle:5,10')->name('contact.send');
foreach (['privacy' => 'privacy-policy', 'terms' => 'terms-and-agreements'] as $name => $uri) {
    Route::get($uri, [\App\Http\Controllers\InfoPageController::class, 'show'])->defaults('slug', $uri)->name($name);
}
Route::get('page/{slug}', [\App\Http\Controllers\InfoPageController::class, 'show'])->name('pages.show');

foreach ($upcoming as $name => [$uri, $title]) {
    Route::get($uri, [PageController::class, 'comingSoon'])
        ->defaults('title', $title)
        ->name($name);
}

/*
|--------------------------------------------------------------------------
| CELPIP practice (live website)
|--------------------------------------------------------------------------
|  /celpip/general                         → the 5 sections (mock test + 4 skills)
|  /celpip/general/mock-tests              → all full mock tests
|  /celpip/general/mock-tests/{test}       → take a full mock test
|  /celpip/general/writing                 → all writing practice tests
|  /celpip/general/writing/{test}          → take one writing practice test
|  (the same addresses work for /celpip/ls)
*/
Route::get('celpip/general', [CelpipPracticeController::class, 'hub'])->defaults('format', 'general')->name('celpip.general');
Route::get('celpip/ls', [CelpipPracticeController::class, 'hub'])->defaults('format', 'ls')->name('celpip.ls');
// IELTS – overview page leading to IELTS Academic and IELTS General Training
Route::view('ielts', 'ielts.landing')->name('ielts');

// CELPIP – overview page leading to CELPIP General and CELPIP LS
Route::get('celpip', \App\Http\Controllers\CelpipLandingController::class)->name('celpip');

// Students: sign in with Google / Microsoft, and their personal portal
Route::get('login', [\App\Http\Controllers\Student\AuthController::class, 'show'])->name('student.login');
Route::get('login/{provider}', [\App\Http\Controllers\Student\AuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'microsoft'])->name('student.redirect');
Route::get('login/{provider}/callback', [\App\Http\Controllers\Student\AuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'microsoft'])->name('student.callback');
Route::post('login/test', [\App\Http\Controllers\Student\AuthController::class, 'testLogin'])
    ->middleware('throttle:10,1')->name('student.test-login');
Route::post('logout', [\App\Http\Controllers\Student\AuthController::class, 'logout'])->name('student.logout');
Route::get('my-portal', [\App\Http\Controllers\Student\PortalController::class, 'index'])->name('student.portal');
Route::post('my-portal/results', [\App\Http\Controllers\Student\PortalController::class, 'storeResult'])
    ->middleware('throttle:30,1')->name('student.results.store');

// Students' Writing answers and Speaking recordings (sent from the test page, evaluated in the admin panel)
Route::post('celpip/submissions', [\App\Http\Controllers\SubmissionController::class, 'store'])
    ->middleware('throttle:20,1')->name('celpip.submissions.store');

/*
|--------------------------------------------------------------------------
| IELTS practice (live website) – works like CELPIP
|--------------------------------------------------------------------------
|  /ielts/academic                          → the 5 sections (mock test + 4 skills)
|  /ielts/academic/mock-tests               → all full mock tests
|  /ielts/academic/speaking                 → all speaking practice tests
|  /ielts/academic/speaking/{test}          → take one speaking test (writing works the same way)
|  (the same addresses work for /ielts/general-training)
*/
$ieltsModules = array_keys(\App\Models\IeltsTest::PUBLIC_MODULES);
Route::get('ielts/academic', [\App\Http\Controllers\IeltsPracticeController::class, 'hub'])->defaults('format', 'academic')->name('ielts.academic');
Route::get('ielts/general-training', [\App\Http\Controllers\IeltsPracticeController::class, 'hub'])->defaults('format', 'general-training')->name('ielts.general');
Route::prefix('ielts/{format}')->whereIn('format', ['academic', 'general-training'])->name('ielts.practice.')->group(function () use ($ieltsModules) {
    Route::get('mock-tests', [\App\Http\Controllers\IeltsPracticeController::class, 'mockList'])->name('mocks');
    Route::get('{module}', [\App\Http\Controllers\IeltsPracticeController::class, 'moduleList'])->whereIn('module', $ieltsModules)->name('module');
    Route::get('{module}/{slug}', [\App\Http\Controllers\IeltsPracticeController::class, 'practice'])->whereIn('module', \App\Models\IeltsTest::BUILT)->name('test');
    Route::get('{module}/{slug}/results', fn (\Illuminate\Http\Request $r) => redirect(preg_replace('#/results$#', '', $r->getPathInfo())))->whereIn('module', \App\Models\IeltsTest::BUILT);
    Route::get('{slug}/speaking', [\App\Http\Controllers\IeltsPracticeController::class, 'oldSpeaking']);
});
Route::get('ielts/vocabulary/{module}/{test}/print', [\App\Http\Controllers\VocabularyController::class, 'print'])->whereIn('module', ['reading', 'listening'])->name('ielts.vocab.print');
Route::get('ielts/vocabulary/{module}/{test}/data', [\App\Http\Controllers\VocabularyController::class, 'data'])->whereIn('module', ['reading', 'listening'])->name('ielts.vocab.data');
Route::get('ielts/vocabulary/{module}/{test}/practise', [\App\Http\Controllers\VocabularyController::class, 'practise'])->whereIn('module', ['reading', 'listening'])->name('ielts.vocab.practise');
Route::post('ielts/listening/{test}/score', [\App\Http\Controllers\IeltsPracticeController::class, 'scoreListening'])
    ->middleware('throttle:30,1')->name('ielts.listening.score');
Route::post('ielts/reading/{test}/score', [\App\Http\Controllers\IeltsPracticeController::class, 'scoreReading'])
    ->middleware('throttle:30,1')->name('ielts.reading.score');
Route::post('ielts/submissions', [\App\Http\Controllers\SubmissionController::class, 'storeIelts'])
    ->middleware('throttle:20,1')->name('ielts.submissions.store');

/* TOEFL iBT (2026 format) practice */
Route::controller(\App\Http\Controllers\ToeflPracticeController::class)->prefix('toefl')->group(function () {
    Route::get('/', 'landing')->name('toefl');
    Route::get('practice-tests', 'hub')->name('toefl.hub');
    Route::get('mock-tests', 'mockList')->name('toefl.mocks');
    Route::get('mock-tests/{slug}', 'mock')->name('toefl.mock');
    Route::post('score', 'score')->middleware('throttle:60,1')->name('toefl.score');
    Route::post('submissions', 'submit')->middleware('throttle:20,1')->name('toefl.submissions.store');
    Route::post('submissions/{submission}/request', 'requestFeedback')->middleware('throttle:30,1')->name('toefl.submissions.request');
    Route::get('{module}', 'moduleList')->whereIn('module', ['reading', 'listening', 'writing', 'speaking'])->name('toefl.module');
    Route::get('{module}/{slug}', 'practice')->whereIn('module', ['reading', 'listening', 'writing', 'speaking'])->name('toefl.test');
});

Route::prefix('celpip/{format}')->whereIn('format', ['general', 'ls'])->name('celpip.practice.')->group(function () {
    Route::get('mock-tests', [CelpipPracticeController::class, 'mockList'])->name('mocks');
    Route::get('mock-tests/{slug}', [CelpipPracticeController::class, 'mock'])->name('mock');
    Route::get('{module}', [CelpipPracticeController::class, 'moduleList'])
        ->whereIn('module', array_keys(\App\Models\CelpipTest::PUBLIC_MODULES))->name('module');
    Route::get('{module}/{slug}', [CelpipPracticeController::class, 'practice'])
        ->whereIn('module', array_keys(\App\Models\CelpipTest::PUBLIC_MODULES))->name('test');
    Route::get('{module}/{slug}/results', fn (\Illuminate\Http\Request $r) => redirect(preg_replace('#/results$#', '', $r->getPathInfo())))
        ->whereIn('module', array_keys(\App\Models\CelpipTest::PUBLIC_MODULES));
    Route::get('mock-tests/{slug}/results', fn (\Illuminate\Http\Request $r) => redirect(preg_replace('#/results$#', '', $r->getPathInfo())));
});

/*
|--------------------------------------------------------------------------
| Sample Answers (live website) – menu items come from app/Support/SampleAnswers.php
|--------------------------------------------------------------------------
|  /sample-answers                                  → overview of all sections
|  /sample-answers/ielts/speaking/part-1           → all sample answers in that section
|  /sample-answers/ielts/speaking/part-1/{slug}    → one sample answer (written in Admin › Blogs)
*/
foreach (\App\Support\SampleAnswers::hubs() as $name => $hub) {
    Route::get($hub['path'], [SampleAnswersController::class, 'hub'])->defaults('hub', $name)->name($name);
}
foreach (\App\Support\SampleAnswers::categories() as $key => $cat) {
    Route::get($cat['path'], [SampleAnswersController::class, 'category'])->defaults('key', $key)->name($cat['route']);
    Route::get($cat['path'] . '/{slug}', [BlogPageController::class, 'showSample'])->defaults('category', $key)->name($cat['route'] . '.post');
}

// Blog pages: one page per category
// There is no "all blogs" page: /blog goes to the first category.
Route::redirect('blog', '/blog/language-learning', 301)->name('blog');
foreach (['blog.language' => 'language-learning', 'blog.ielts' => 'ielts', 'blog.celpip' => 'celpip', 'blog.toefl' => 'toefl', 'blog.study' => 'study-methods'] as $name => $cat) {
    Route::get('blog/' . $cat, [BlogPageController::class, 'category'])->defaults('category', $cat)->name($name);
}

// A single blog, e.g. /blog/ielts/how-to-score-band-8
Route::get('blog/{category}/{slug}', [BlogPageController::class, 'show'])
    ->whereIn('category', array_keys(\App\Models\Blog::CATEGORIES))
    ->name('blogs.show');

// Author page, e.g. /author/sarah-ahmed
Route::get('author/{author:slug}', [AuthorPageController::class, 'show'])->name('authors.show');

/*
|--------------------------------------------------------------------------
| Admin panel  (everything under /admin)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // Login / logout
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    // Everything below needs the admin to be logged in
    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Homepage (dashboard with stats)
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Language learning (modules and lessons)
        Route::prefix('languages/{language}')->whereIn('language', array_keys(\App\Models\LanguageModule::LANGUAGES))->name('languages.')->group(function () {
            $c = \App\Http\Controllers\Admin\LanguageAdminController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('modules/new', [$c, 'createModule'])->name('modules.create');
            Route::post('modules', [$c, 'storeModule'])->name('modules.store');
            Route::get('modules/{module}/edit', [$c, 'editModule'])->name('modules.edit');
            Route::put('modules/{module}', [$c, 'updateModule'])->name('modules.update');
            Route::delete('modules/{module}', [$c, 'destroyModule'])->name('modules.destroy');
            Route::get('modules/{module}/lessons', [$c, 'lessons'])->name('lessons');
            Route::get('modules/{module}/lessons/new', [$c, 'createLesson'])->name('lessons.create');
            Route::post('modules/{module}/lessons', [$c, 'storeLesson'])->name('lessons.store');
            Route::get('modules/{module}/lessons/{lesson}/edit', [$c, 'editLesson'])->name('lessons.edit');
            Route::put('modules/{module}/lessons/{lesson}', [$c, 'updateLesson'])->name('lessons.update');
            Route::delete('modules/{module}/lessons/{lesson}', [$c, 'destroyLesson'])->name('lessons.destroy');
        });

        // Info pages and student stories (home page)
        Route::resource('info-pages', \App\Http\Controllers\Admin\InfoPageController::class)->except('show')->parameters(['info-pages' => 'page']);
        Route::resource('achievements', \App\Http\Controllers\Admin\AchievementController::class)->except('show');

        // Site settings (logo, footer, social links, contact email) and home page content (hero, exam cards, study-method cards)
        Route::get('site-settings', [\App\Http\Controllers\Admin\SiteSettingController::class, 'site'])->name('site-settings.site');
        Route::post('site-settings', [\App\Http\Controllers\Admin\SiteSettingController::class, 'saveSite'])->name('site-settings.site.save');
        Route::get('contact-details', [\App\Http\Controllers\Admin\SiteSettingController::class, 'contact'])->name('site-settings.contact');
        Route::post('contact-details', [\App\Http\Controllers\Admin\SiteSettingController::class, 'saveContact'])->name('site-settings.contact.save');
        Route::get('messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])->name('messages.index');
        Route::post('messages/{message}/read', [\App\Http\Controllers\Admin\MessageController::class, 'toggle'])->name('messages.toggle');
        Route::delete('messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'destroy'])->name('messages.destroy');
        Route::get('homepage-content', [\App\Http\Controllers\Admin\SiteSettingController::class, 'home'])->name('site-settings.home');
        Route::post('homepage-content', [\App\Http\Controllers\Admin\SiteSettingController::class, 'saveHome'])->name('site-settings.home.save');

        // 1 – Blogs
        Route::get('blogs', [BlogController::class, 'index'])->name('blogs.index');
        Route::get('blogs/new', [BlogController::class, 'create'])->name('blogs.create');
        Route::post('blogs', [BlogController::class, 'store'])->name('blogs.store');
        Route::get('blogs/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
        Route::put('blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::delete('blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');
        Route::post('blogs/editor-image', [BlogController::class, 'uploadEditorImage'])->name('blogs.editor-image');
        // Files added inside any text editor (pictures, audio, video, documents)
        Route::post('editor-upload', \App\Http\Controllers\Admin\EditorUploadController::class)->name('editor-upload');

        // 2 & 3 – CELPIP tests
        Route::get('celpip-tests', [CelpipTestController::class, 'index'])->name('celpip.index');
        Route::get('celpip-tests/new', [CelpipTestController::class, 'create'])->name('celpip.create');
        Route::post('celpip-tests', [CelpipTestController::class, 'store'])->name('celpip.store');
        Route::get('celpip-tests/{test}/edit', [CelpipTestController::class, 'edit'])->name('celpip.edit');
        Route::put('celpip-tests/{test}', [CelpipTestController::class, 'update'])->name('celpip.update');
        Route::delete('celpip-tests/{test}', [CelpipTestController::class, 'destroy'])->name('celpip.destroy');
        Route::get('celpip-tests/{test}/{module}', [CelpipTestController::class, 'module'])
            ->whereIn('module', ['writing', 'speaking', 'reading', 'listening'])
            ->name('celpip.module');
        Route::put('celpip-tests/{test}/{module}', [CelpipTestController::class, 'saveModule'])
            ->whereIn('module', ['writing', 'speaking', 'reading', 'listening'])
            ->name('celpip.module.save');
        // TOEFL iBT tests (2026 format)
        Route::get('seo-pages', [\App\Http\Controllers\Admin\SeoPageController::class, 'index'])->name('seo.index');
        Route::post('seo-pages', [\App\Http\Controllers\Admin\SeoPageController::class, 'save'])->name('seo.save');
        Route::put('test-seo/{exam}/{test}', [\App\Http\Controllers\Admin\TestSeoController::class, 'save'])->whereIn('exam', ['ielts', 'celpip', 'toefl'])->whereNumber('test')->name('test-seo.save');
        Route::get('toefl-tests', [\App\Http\Controllers\Admin\ToeflTestController::class, 'index'])->name('toefl.index');
        Route::get('toefl-tests/new', [\App\Http\Controllers\Admin\ToeflTestController::class, 'create'])->name('toefl.create');
        Route::post('toefl-tests', [\App\Http\Controllers\Admin\ToeflTestController::class, 'store'])->name('toefl.store');
        Route::get('toefl-tests/{test}/edit', [\App\Http\Controllers\Admin\ToeflTestController::class, 'edit'])->name('toefl.edit');
        Route::put('toefl-tests/{test}', [\App\Http\Controllers\Admin\ToeflTestController::class, 'update'])->name('toefl.update');
        Route::delete('toefl-tests/{test}', [\App\Http\Controllers\Admin\ToeflTestController::class, 'destroy'])->name('toefl.destroy');
        Route::post('toefl-tests/{test}/media', [\App\Http\Controllers\Admin\ToeflTestController::class, 'uploadMedia'])->name('toefl.media');
        Route::get('toefl-tests/{test}/{module}', [\App\Http\Controllers\Admin\ToeflTestController::class, 'module'])->whereIn('module', ['reading', 'listening', 'writing', 'speaking'])->name('toefl.module');
        Route::put('toefl-tests/{test}/{module}', [\App\Http\Controllers\Admin\ToeflTestController::class, 'saveModule'])->whereIn('module', ['reading', 'listening', 'writing', 'speaking'])->name('toefl.module.save');
        Route::post('celpip-tests/{test}/media', [CelpipTestController::class, 'uploadMedia'])->name('celpip.media');

        // English Level Tests
        Route::prefix('level-tests')->name('level.')->controller(\App\Http\Controllers\Admin\LevelTestController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('new', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{test}/edit', 'edit')->name('edit');
            Route::put('{test}', 'update')->name('update');
            Route::delete('{test}', 'destroy')->name('destroy');
            Route::get('{test}/section/{n}', 'section')->whereNumber('n')->name('section');
            Route::put('{test}/section/{n}', 'saveSection')->whereNumber('n')->name('section.save');
            Route::post('{test}/section/{n}/questions', 'saveQuestion')->whereNumber('n')->name('question.save');
            Route::delete('{test}/questions/{question}', 'destroyQuestion')->name('question.destroy');
        });

        // Manage Authors
        Route::get('authors', [AuthorController::class, 'index'])->name('authors.index');
        Route::get('authors/new', [AuthorController::class, 'create'])->name('authors.create');
        Route::post('authors', [AuthorController::class, 'store'])->name('authors.store');
        Route::get('authors/{author}/edit', [AuthorController::class, 'edit'])->name('authors.edit');
        Route::put('authors/{author}', [AuthorController::class, 'update'])->name('authors.update');
        Route::delete('authors/{author}', [AuthorController::class, 'destroy'])->name('authors.destroy');

        // Manage IELTS – All IELTS Tests / Add an IELTS Test / Speaking questions
        Route::get('ielts-tests', [\App\Http\Controllers\Admin\IeltsTestController::class, 'index'])->name('ielts.index');
        Route::get('ielts-tests/new', [\App\Http\Controllers\Admin\IeltsTestController::class, 'create'])->name('ielts.create');
        Route::post('ielts-tests', [\App\Http\Controllers\Admin\IeltsTestController::class, 'store'])->name('ielts.store');
        Route::get('ielts-tests/{test}/edit', [\App\Http\Controllers\Admin\IeltsTestController::class, 'edit'])->name('ielts.edit');
        Route::put('ielts-tests/{test}', [\App\Http\Controllers\Admin\IeltsTestController::class, 'update'])->name('ielts.update');
        Route::delete('ielts-tests/{test}', [\App\Http\Controllers\Admin\IeltsTestController::class, 'destroy'])->name('ielts.destroy');
        Route::get('ielts-tests/{test}/{module}', [\App\Http\Controllers\Admin\IeltsTestController::class, 'module'])
            ->whereIn('module', ['writing', 'speaking'])->name('ielts.module');
        Route::put('ielts-tests/{test}/{module}', [\App\Http\Controllers\Admin\IeltsTestController::class, 'saveModule'])
            ->whereIn('module', ['writing', 'speaking'])->name('ielts.module.save');
        // IELTS (GT) Reading: home page with buttons + preview, passages, and one form per question type
        Route::prefix('ielts-tests/{test}/reading')->name('ielts.reading')->controller(\App\Http\Controllers\Admin\IeltsReadingController::class)->group(function () {
            Route::get('/', 'home');
            Route::get('passages', 'passages')->name('.passages');
            Route::put('passages', 'savePassages')->name('.passages.save');
            Route::get('questions/new/{type}', 'create')->name('.create');
            Route::get('questions/{group}/edit', 'edit')->name('.edit');
            Route::post('questions', 'save')->name('.save');
            Route::delete('questions/{group}', 'destroy')->name('.destroy');
            Route::post('questions/{group}/move/{dir}', 'move')->whereIn('dir', ['up', 'down'])->name('.move');
            Route::post('image', 'saveImage')->name('.image');
        });
        Route::prefix('ielts-tests/{test}/listening')->name('ielts.listening')->controller(\App\Http\Controllers\Admin\IeltsListeningController::class)->group(function () {
            Route::get('/', 'home');
            Route::get('passages', 'passages')->name('.passages');
            Route::put('passages', 'savePassages')->name('.passages.save');
            Route::get('questions/new/{type}', 'create')->name('.create');
            Route::get('questions/{group}/edit', 'edit')->name('.edit');
            Route::post('questions', 'save')->name('.save');
            Route::delete('questions/{group}', 'destroy')->name('.destroy');
            Route::post('questions/{group}/move/{dir}', 'move')->whereIn('dir', ['up', 'down'])->name('.move');
            Route::post('image', 'saveImage')->name('.image');
            Route::post('audio', 'saveAudio')->name('.audio');
            Route::delete('audio', 'removeAudio')->name('.audio.remove');
        });
        // Vocabulary Builder: difficult words of a Reading / Listening test (suggested by AI, reviewed here)
        Route::prefix('ielts-tests/{test}/vocabulary/{module}')->whereIn('module', ['reading', 'listening'])->name('ielts.vocab')
            ->controller(\App\Http\Controllers\Admin\VocabularyController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('generate', 'generate')->name('.generate');
                Route::post('translate', 'translateMissing')->name('.translate');
                Route::post('add', 'add')->name('.add');
                Route::put('/', 'save')->name('.save');
            });
        // Vocabulary Builder for CELPIP Reading / Listening and for Writing / Speaking topics
        Route::prefix('celpip-tests/{test}/vocabulary/{module}')->whereIn('module', ['reading', 'listening'])->name('celpip.vocab')
            ->controller(\App\Http\Controllers\Admin\VocabAdminController::class)->group(function () {
                Route::get('/', 'celpipIndex');
                Route::post('generate', 'celpipGenerate')->name('.generate');
                Route::post('translate', 'celpipTranslate')->name('.translate');
                Route::post('add', 'celpipAdd')->name('.add');
                Route::put('/', 'celpipSave')->name('.save');
                Route::put('source', 'celpipSource')->name('.source');
            });
        Route::prefix('listening-spellings')->name('spellings')->controller(\App\Http\Controllers\Admin\SpellingController::class)->group(function () {
            Route::get('/', 'index');
            Route::put('/', 'save')->name('.save');
            Route::post('rescan', 'rescan')->name('.rescan');
            Route::post('sort', 'sortAll')->name('.sort');
            Route::post('add', 'add')->name('.add');
        });
        Route::get('vocabulary-overview', [\App\Http\Controllers\Admin\VocabAdminController::class, 'overview'])->name('vocab-overview');
        Route::prefix('vocabulary-topics')->name('vocab-topics')->controller(\App\Http\Controllers\Admin\VocabAdminController::class)->group(function () {
            Route::get('/', 'topics');
            Route::post('/', 'topicStore')->name('.store');
            Route::get('{topic}', 'topicShow')->name('.show');
            Route::put('{topic}', 'topicUpdate')->name('.update');
            Route::delete('{topic}', 'topicDestroy')->name('.destroy');
            Route::post('{topic}/generate', 'topicGenerate')->name('.generate');
            Route::post('{topic}/translate', 'topicTranslate')->name('.translate');
            Route::post('{topic}/add', 'topicAdd')->name('.add');
            Route::put('{topic}/words', 'topicSave')->name('.save');
        });
        Route::post('ielts-tests/{test}/media', [\App\Http\Controllers\Admin\IeltsTestController::class, 'uploadMedia'])->name('ielts.media');

        // CELPIP / IELTS Evaluation – students' Writing answers and Speaking recordings
        Route::get('evaluations/{exam}', [\App\Http\Controllers\Admin\EvaluationController::class, 'index'])
            ->whereIn('exam', ['celpip', 'ielts', 'toefl'])->name('evaluations.index');
        Route::get('evaluations/{exam}/{submission}', [\App\Http\Controllers\Admin\EvaluationController::class, 'show'])
            ->whereIn('exam', ['celpip', 'ielts', 'toefl'])->name('evaluations.show');
        Route::put('evaluations/{exam}/{submission}', [\App\Http\Controllers\Admin\EvaluationController::class, 'update'])
            ->whereIn('exam', ['celpip', 'ielts', 'toefl'])->name('evaluations.update');
        Route::delete('evaluations/{exam}/{submission}', [\App\Http\Controllers\Admin\EvaluationController::class, 'destroy'])
            ->whereIn('exam', ['celpip', 'ielts', 'toefl'])->name('evaluations.destroy');
        Route::get('evaluations/{exam}/{submission}/audio/{file}', [\App\Http\Controllers\Admin\EvaluationController::class, 'audio'])
            ->whereIn('exam', ['celpip', 'ielts', 'toefl'])->name('evaluations.audio');

        // Course requests (Get a Quote form) and paid bookings
        Route::get('course-requests', [\App\Http\Controllers\Admin\CourseRequestController::class, 'index'])->name('course-requests.index');
        Route::post('course-requests/{courseRequest}/status', [\App\Http\Controllers\Admin\CourseRequestController::class, 'status'])->name('course-requests.status');
        Route::delete('course-requests/{courseRequest}', [\App\Http\Controllers\Admin\CourseRequestController::class, 'destroy'])->name('course-requests.destroy');
        Route::get('course-bookings', [\App\Http\Controllers\Admin\CourseBookingController::class, 'index'])->name('course-bookings.index');

        // Courses (cards on /courses)
        Route::post('courses/seed', [\App\Http\Controllers\Admin\CourseController::class, 'seed'])->name('courses.seed');
        Route::post('courses/sections', [\App\Http\Controllers\Admin\CourseController::class, 'saveSections'])->name('courses.sections');
        Route::resource('courses', \App\Http\Controllers\Admin\CourseController::class)->except('show');

        // 4 – Manage Paid Clients
        Route::get('clients/new', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('clients/{client}/end', [ClientController::class, 'end'])->name('clients.end');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });
});

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

/** A paid course shown on /courses (written in Admin → Courses). */
class Course extends Model
{
    public const EXAMS = ['ielts' => 'IELTS', 'celpip' => 'CELPIP', 'toefl' => 'TOEFL'];

    protected $table = 'course_plans';

    protected $fillable = ['exam', 'title', 'slug', 'summary', 'badge', 'chips', 'features', 'note', 'price', 'currency', 'button_label', 'button_url', 'is_featured', 'is_published', 'sort_order'];

    protected $casts = ['is_featured' => 'boolean', 'is_published' => 'boolean', 'price' => 'float'];

    public function chipList(): array
    {
        return self::lines($this->chips);
    }

    public function featureList(): array
    {
        return self::lines($this->features);
    }

    /** "$20", "$49.50" – or "20 EUR" for other currencies. */
    public function priceLabel(): string
    {
        $n = rtrim(rtrim(number_format($this->price, 2, '.', ','), '0'), '.');

        return strtoupper($this->currency) === 'USD' ? '$' . $n : $n . ' ' . strtoupper($this->currency);
    }

    /** The number part of the price for the cards: "$20" (USD) or "20" (other currencies; the code is shown small next to it). */
    public function priceAmount(): string
    {
        $n = rtrim(rtrim(number_format($this->price, 2, '.', ','), '0'), '.');

        return strtoupper($this->currency) === 'USD' ? '$' . $n : $n;
    }

    /** Where "Purchase Now" goes: the address written in the admin, else the payments page (the contact page until it exists). */
    public function buttonHref(): string
    {
        if (filled($this->button_url)) return SiteSetting::href($this->button_url);

        return Route::has('purchase') ? route('purchase', ['course' => $this->slug]) : route('contact');
    }

    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text))));
    }

    public static function sectionDefaults(): array
    {
        return [
            'ielts'  => ['title' => 'IELTS Courses',  'text' => 'Live classes, mock tests and recorded lessons for IELTS Academic and General Training.'],
            'celpip' => ['title' => 'CELPIP Courses', 'text' => 'Courses built around the CELPIP General and LS computer-based test, for your Canadian goals.'],
            'toefl'  => ['title' => 'TOEFL Courses',  'text' => 'Courses for the TOEFL iBT: Reading, Listening, Writing and Speaking.'],
        ];
    }

    /** Title and text of the IELTS / CELPIP / TOEFL blocks (edited in Admin → Courses). */
    public static function sections(): array
    {
        $saved = SiteSetting::group('courses');
        $out = [];
        foreach (self::sectionDefaults() as $k => $d) {
            $out[$k] = ['title' => filled($saved[$k]['title'] ?? null) ? $saved[$k]['title'] : $d['title'], 'text' => filled($saved[$k]['text'] ?? null) ? $saved[$k]['text'] : $d['text']];
        }

        return $out;
    }

    /** The starting set of courses (IELTS, CELPIP, TOEFL). Used by the database update and by the "Add the standard courses" button. */
    public static function seedDefaults(): void
    {
        if (self::query()->exists()) return;

        // Starting courses (edit or remove them in Admin → Courses)
        $exams = [
            'ielts'  => ['IELTS',  'IELTS',  ['Listening' => ['Live online classes with an expert IELTS tutor', 'All 4 parts covered: form completion, multiple choice, maps and matching', 'Note-taking and spelling techniques that save marks', 'Practice with Cambridge-style recordings', 'Class handouts and homework'],
                                              'Reading'   => ['Live online classes with an expert IELTS tutor', 'Skimming and scanning for speed', 'True / False / Not Given, matching headings and summary completion', 'Time management for the 60-minute paper', 'Academic and General Training passages'],
                                              'Writing'   => ['Live online classes with an expert IELTS tutor', 'Task 1 (report or letter) and Task 2 (essay) structures', 'Band-descriptor guide: coherence, vocabulary, grammar', 'Model answers and useful templates', 'Feedback on your written practice']  ,
                                              'Speaking'  => ['Live online classes with an expert IELTS tutor', 'Techniques for Part 1, Part 2 and Part 3', 'Fluency, pronunciation and vocabulary building', 'Mock speaking practice with feedback', 'Ideas for the most common cue-card topics']]],
            'celpip' => ['CELPIP', 'CELPIP', ['Listening' => ['Live online classes with an expert CELPIP tutor', 'All 6 listening parts explained', 'Note-taking and prediction skills', 'Practice with real-test style audio', 'Class handouts and homework'],
                                              'Reading'   => ['Live online classes with an expert CELPIP tutor', 'All 4 reading parts explained', 'Reading for correspondence, diagrams and viewpoints', 'Time management for every part', 'Class handouts and homework'],
                                              'Writing'   => ['Live online classes with an expert CELPIP tutor', 'Task 1 (email) and Task 2 (survey response) structures', 'Tone, vocabulary and grammar that earn higher CLB levels', 'Model answers and useful templates', 'Feedback on your written practice'],
                                              'Speaking'  => ['Live online classes with an expert CELPIP tutor', 'All 8 speaking tasks with answer frameworks', 'Using the preparation time well', 'Fluency, pronunciation and vocabulary building', 'Mock speaking practice with feedback']]],
            'toefl'  => ['TOEFL',  'TOEFL iBT', ['Listening' => ['Live online classes with an expert TOEFL tutor', 'Lectures, conversations and short listening tasks', 'Note-taking and prediction skills', 'Practice with exam-style audio', 'Class handouts and homework'],
                                              'Reading'   => ['Live online classes with an expert TOEFL tutor', 'Academic passage strategies', 'Vocabulary-in-context and inference questions', 'Time management for every task', 'Class handouts and homework'],
                                              'Writing'   => ['Live online classes with an expert TOEFL tutor', 'Build a sentence, email and academic discussion tasks', 'Clear structure, grammar and vocabulary', 'Model answers and useful templates', 'Feedback on your written practice'],
                                              'Speaking'  => ['Live online classes with an expert TOEFL tutor', 'Listen-and-repeat and interview-style tasks', 'Answer frameworks that keep you on time', 'Fluency, pronunciation and vocabulary building', 'Mock speaking practice with feedback']]],
        ];

        $now = now();
        $order = 0;
        foreach ($exams as $key => [$short, $long, $modules]) {
            $rows = [];
            foreach ($modules as $module => $features) {
                $rows[] = ['title' => "$short $module Course", 'slug' => "$key-" . strtolower($module) . '-course',
                    'summary' => "Focused live classes to raise your $long $module score.",
                    'chips' => "7 Classes\n1 hour each class", 'features' => implode("\n", $features), 'price' => 20];
            }
            $rows[] = ['title' => "$short Complete Preparation Course", 'slug' => "$key-complete-preparation-course", 'badge' => 'Best value',
                'summary' => "Everything you need for $long in one course: all four modules, step by step.",
                'chips' => "22 Classes\n1 Month\n1 hour each class", 'is_featured' => true, 'price' => 50,
                'features' => "All modules covered: Listening, Reading, Writing and Speaking\nLive online classes with an expert tutor\nA weekly practice test with feedback\nPersonal study plan\nRecordings of every class\nTutor support between classes"];
            $rows[] = ['title' => "$short Computer Based Mock Tests", 'slug' => "$key-computer-based-mock-tests",
                'summary' => "Test yourself on the computer, exactly like exam day.",
                'chips' => "30 Full Length Mock Tests\nComputer Based\nAccess for 1 Month", 'price' => 15,
                'features' => "30 updated full length mock tests\nTests developed by our expert tutor\nExpert guidance available\nInstant marking for Reading and Listening\nDetailed answer explanations"];
            $rows[] = ['title' => "$short Recorded Lessons", 'slug' => "$key-recorded-lessons",
                'summary' => "Watch at your own pace, whenever it suits you.",
                'chips' => "All modules covered\nSelf-paced", 'price' => 20,
                'features' => "Listening, Reading, Writing and Speaking lessons\nWatch anytime on any device\nLearn at your own speed\nDownloadable class notes",
                'note' => 'Expert feedback not available'];

            foreach ($rows as $r) {
                \Illuminate\Support\Facades\DB::table('course_plans')->insert($r + ['exam' => $key, 'badge' => $r['badge'] ?? null, 'note' => $r['note'] ?? null, 'is_featured' => $r['is_featured'] ?? false,
                    'currency' => 'USD', 'button_label' => 'Purchase Now', 'is_published' => true, 'sort_order' => ++$order, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}

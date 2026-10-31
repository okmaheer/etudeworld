<?php

namespace App\Http\Controllers;

use App\Models\StudyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/** Study Planner: asks the AI for a week-by-week plan (limited per visitor and per day). */
class StudyPlannerController extends Controller
{
    private const EXAMS = ['ielts-academic' => 'IELTS Academic', 'ielts-gt' => 'IELTS General Training', 'celpip' => 'CELPIP', 'toefl' => 'TOEFL iBT'];
    private const SKILLS = ['Listening', 'Reading', 'Writing', 'Speaking'];

    /** The visitor's saved plan (if they already made one). */
    public function mine(Request $request)
    {
        return response()->json(['plan' => StudyPlan::forVisitor($request)?->plan]);
    }

    public function plan(Request $request)
    {
        // One plan per visitor (same browser or same IP address).
        if ($saved = StudyPlan::forVisitor($request)) {
            return response()->json(['error' => 'You have already made your study plan. Each visitor can make only one.', 'plan' => $saved->plan], 409);
        }
        $data = $request->validate([
            'exam' => 'required|in:' . implode(',', array_keys(self::EXAMS)),
            'target' => 'required|string|max:40',
            'current' => 'nullable|string|max:60',
            'date' => 'required|date|after:today',
            'hours' => 'required|numeric|min:0.5|max:12',
            'weak' => 'nullable|array',
            'weak.*' => 'in:' . implode(',', self::SKILLS),
        ]);

        $key = config('services.openai.key');
        if (! $key) {
            return response()->json(['error' => 'The Study Planner is not available right now. Please try again later.'], 503);
        }
        $dayKey = 'planner-' . now()->format('Y-m-d');
        if (Cache::get($dayKey, 0) >= config('services.planner.daily_cap')) {
            return response()->json(['error' => 'The Study Planner has reached its limit for today. Please come back tomorrow.'], 429);
        }

        $days = max(1, (int) now()->startOfDay()->diffInDays(Carbon::parse($data['date'])->startOfDay()));
        $daily = $days <= 14;                                   // a short time: plan day by day
        $steps = $daily ? $days : min(12, max(1, (int) ceil($days / 7)));
        $unit = $daily ? 'day' : 'week';
        $weak = $data['weak'] ?? [];
        $exam = self::EXAMS[$data['exam']];

        $prompt = "You are an experienced English exam teacher. Write a realistic study plan.\n"
            . "Today: " . now()->format('Y-m-d') . "\nExam: $exam\nTarget score: " . $data['target'] . "\nCurrent level / last score: " . ($data['current'] ?: 'not given') . "\n"
            . "Exam date: " . $data['date'] . " ($days " . ($days === 1 ? 'day' : 'days') . " from today)\nStudy time: " . $data['hours'] . " hours per day\n"
            . "Weakest skills: " . ($weak ? implode(', ', $weak) : 'not given') . "\n\n"
            . "IMPORTANT: the student has only $days " . ($days === 1 ? 'day' : 'days') . " until the exam, so the plan must fit exactly into that time. Never plan beyond the exam date. "
            . ($daily
                ? "Make exactly $steps DAILY steps: step 1 is today and the last step is the day before the exam (use 'day' numbers 1 to $steps). Each day must be realistic for " . $data['hours'] . " hours of study. "
                    . "Give each day a clear focus and 2 to 4 specific tasks. Do not try to teach everything: choose the most useful things for the time left (exam format, the weakest skills, timed practice, reviewing mistakes). Make the last day light: a short review, rest and exam-day preparation. "
                : "Make exactly $steps WEEKLY steps" . ($days > 84 ? " (the exam is more than 12 weeks away, so plan the LAST 12 weeks before the exam)" : '') . ". "
                    . "Give each week a clear focus and 3 to 5 specific tasks (for example task types, timed practice, vocabulary, reviewing mistakes). Make the last week about full mock tests and rest. ")
            . "Spend more time on the weakest skills. Use simple English.\n"
            . 'Answer ONLY with JSON: {"summary":"2-3 sentences","steps":[{"n":1,"focus":"short title","tasks":["task","task"]}],"tips":["tip","tip","tip"]}';

        try {
            $res = Http::withToken($key)->timeout(90)->post(rtrim(config('services.openai.base'), '/') . '/chat/completions', [
                'model' => config('services.openai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'We could not reach the planner. Please try again.'], 502);
        }
        $plan = $res->successful() ? json_decode((string) $res->json('choices.0.message.content'), true) : null;
        if (! is_array($plan) || ! is_array($plan['steps'] ?? null) || ! $plan['steps']) {
            return response()->json(['error' => 'We could not make your plan this time. Please try again.'], 502);
        }
        Cache::add($dayKey, 0, now()->addDay());
        Cache::increment($dayKey);

        $clean = fn ($v) => mb_substr(trim(strip_tags((string) $v)), 0, 400);
        $weeksOut = [];
        foreach (array_slice($plan['steps'], 0, $steps) as $i => $w) {
            $weeksOut[] = ['n' => $i + 1, 'focus' => $clean($w['focus'] ?? ''), 'tasks' => array_map($clean, array_slice((array) ($w['tasks'] ?? []), 0, 6))];
        }

        $out = [
            'exam' => $exam, 'target' => $data['target'], 'date' => $data['date'], 'hours' => $data['hours'], 'unit' => $unit, 'days' => $days,
            'summary' => $clean($plan['summary'] ?? ''), 'steps' => $weeksOut,
            'tips' => array_map($clean, array_slice((array) ($plan['tips'] ?? []), 0, 5)),
        ];
        $token = Str::random(40);
        StudyPlan::create(['token' => $token, 'ip' => (string) $request->ip(), 'plan' => $out]);

        return response()->json($out)->cookie('ew_planner', $token, 60 * 24 * 365 * 5);
    }
}

<?php

namespace App\Http\Controllers;

class ToolsController extends Controller
{
    /** key => address, title, short text for the hub, page description */
    public const PAGES = [
        'study-planner' => [
            'uri' => 'study-planner',
            'title' => 'Study Planner',
            'blurb' => 'Tell us your exam, target score and exam date. Get a week-by-week study plan you can download.',
            'desc' => 'Free IELTS, CELPIP and TOEFL study planner. Enter your target score, exam date and daily study time and get a personal week-by-week plan with a PDF download.',
        ],
        'ielts-calculator' => [
            'uri' => 'ielts-overall-score-calculator',
            'title' => 'IELTS Overall Score Calculator',
            'blurb' => 'Enter your four band scores and get your overall band, rounded the official way.',
            'desc' => 'Free IELTS overall band score calculator for Academic and General Training. Enter Listening, Reading, Writing and Speaking and see how your overall band is rounded.',
        ],
        'ielts-to-toefl' => [
            'uri' => 'ielts-to-toefl-converter',
            'title' => 'IELTS to TOEFL Converter',
            'blurb' => 'See the TOEFL iBT scores (1–6 scale) that match your IELTS band.',
            'desc' => 'Convert an IELTS band score to the matching TOEFL iBT score on the current 1–6 scale, using the official ETS comparison table.',
        ],
        'ielts-to-pte' => [
            'uri' => 'ielts-to-pte-converter',
            'title' => 'IELTS to PTE Converter',
            'blurb' => 'See the PTE Academic scores that match your IELTS band.',
            'desc' => 'Convert an IELTS band score to the matching PTE Academic score for each skill, using the official IELTS comparison table.',
        ],
        'ielts-to-celpip' => [
            'uri' => 'ielts-to-celpip-converter',
            'title' => 'IELTS to CELPIP Converter',
            'blurb' => 'Turn your IELTS band scores into CLB and CELPIP levels for Canadian immigration.',
            'desc' => 'Convert IELTS General Training band scores to Canadian Language Benchmark (CLB) and CELPIP levels for each skill, using the official IRCC table.',
        ],
        'ielts-to-cefr' => [
            'uri' => 'ielts-to-cefr-converter',
            'title' => 'IELTS to CEFR Converter',
            'blurb' => 'Find the CEFR level (A1 to C2) that matches your IELTS band.',
            'desc' => 'Convert an IELTS band score to its CEFR level (A1 to C2) with a plain-English description of what the level means.',
        ],
    ];

    public function show(string $tool)
    {
        abort_unless(isset(self::PAGES[$tool]), 404);

        return view('tools.show', ['tool' => $tool, 'page' => self::PAGES[$tool], 'pages' => self::PAGES]);
    }
}

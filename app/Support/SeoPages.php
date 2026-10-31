<?php

namespace App\Support;

use App\Models\CelpipTest;
use App\Models\IeltsTest;
use App\Models\SeoPage;

/**
 * The exam list pages whose search title, description, heading and text can be edited
 * (Admin → SEO Pages), and the texts they use when nothing was entered.
 */
class SeoPages
{
    public const PAGES = ['landing' => 'Exam home page', 'hub' => 'Practice page (all sections)', 'mock' => 'Full mock tests',
        'reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing', 'speaking' => 'Speaking'];

    /** group key => [label, exam, the address part, the sections it has] */
    public static function groups(): array
    {
        $celpipLs = array_values(array_filter(array_keys(CelpipTest::PUBLIC_MODULES), fn ($m) => CelpipTest::MODULES[$m]['ls']));

        return [
            'ielts-academic' => ['label' => 'IELTS Academic', 'exam' => 'ielts', 'path' => '/ielts/academic', 'modules' => array_keys(IeltsTest::PUBLIC_MODULES)],
            'ielts-gt'       => ['label' => 'IELTS General Training', 'exam' => 'ielts', 'path' => '/ielts/general-training', 'modules' => array_keys(IeltsTest::PUBLIC_MODULES)],
            'celpip-general' => ['label' => 'CELPIP General', 'exam' => 'celpip', 'path' => '/celpip/general', 'modules' => array_keys(CelpipTest::PUBLIC_MODULES)],
            'celpip-ls'      => ['label' => 'CELPIP LS', 'exam' => 'celpip', 'path' => '/celpip/ls', 'modules' => $celpipLs],
            'toefl'          => ['label' => 'TOEFL iBT', 'exam' => 'toefl', 'path' => '/toefl/mock-tests', 'modules' => array_keys(Toefl::PUBLIC)],
        ];
    }

    /** The exam home pages (/ielts, /celpip, /toefl): only the search title and description can be edited. */
    public static function landings(): array
    {
        return ['ielts' => ['label' => 'IELTS home page', 'path' => '/ielts'], 'celpip' => ['label' => 'CELPIP home page', 'path' => '/celpip'],
            'toefl' => ['label' => 'TOEFL home page', 'path' => '/toefl']];
    }

    /** The page address, for showing in the admin. */
    public static function url(string $group, string $page): string
    {
        $g = self::groups()[$group];
        if ($group === 'toefl') {
            return $page === 'hub' ? '/toefl/practice-tests' : ($page === 'mock' ? '/toefl/mock-tests' : '/toefl/' . $page);
        }

        return $g['path'] . ($page === 'hub' ? '' : '/' . ($page === 'mock' ? 'mock-tests' : $page));
    }

    private static function info(string $group, string $page): ?array
    {
        $exam = self::groups()[$group]['exam'];

        return match ($exam) {
            'ielts'  => IeltsTest::PUBLIC_MODULES[$page] ?? null,
            'celpip' => CelpipTest::PUBLIC_MODULES[$page] ?? null,
            default  => Toefl::PUBLIC[$page] ?? null,
        };
    }

    /** The text used when the admin leaves a field empty. */
    public static function defaults(string $group, string $page): array
    {
        $g = self::groups()[$group];
        $app = config('app.name');
        $label = $g['label'];
        $isToefl = $group === 'toefl';
        $info = self::info($group, $page);

        if ($page === 'landing') {
            $d = ['ielts' => ['IELTS – IELTS Academic & General Training Practice Tests – ' . $app, 'IELTS Academic and IELTS General Training practice tests for Listening, Reading, Writing and Speaking, with real timing.'],
                'celpip' => ['CELPIP – CELPIP General & CELPIP LS Practice Tests – ' . $app, 'Free and premium CELPIP practice tests for CELPIP General and CELPIP LS, in the same format as the real computer-based test.'],
                'toefl' => ['TOEFL iBT – Updated 2026 Format Practice Tests – ' . $app, 'Practise the TOEFL iBT in the updated 2026 format: Reading, Listening, Writing and Speaking tasks, full mock tests with real timing, instant Reading and Listening scores and teacher feedback.']][$g['exam']];

            return ['title' => $d[0], 'description' => $d[1], 'h1' => '', 'intro' => ''];
        }
        if ($page === 'hub') {
            $mods = implode(', ', array_map(fn ($m) => self::info($group, $m)['label'], $g['modules']));

            return [
                'title' => $isToefl ? 'Free TOEFL iBT Practice Tests & Full Mock Exams (2026 Format) – ' . $app : 'Free ' . $label . ' Practice Tests & Full Mock Exams – ' . $app,
                'description' => $isToefl ? 'Practise the updated 2026 TOEFL iBT online: full mock exams and practice tests for Reading, Listening, Writing and Speaking, with real exam timing.'
                    : 'Practise for ' . $label . ' online: full mock exams and practice tests for ' . $mods . ', with real exam timing.',
                'h1' => $isToefl ? 'TOEFL iBT Practice Tests – 2026 Format' : $label . ' Practice Tests',
                'intro' => $isToefl ? 'Take a complete mock exam under real test conditions, or sharpen one skill at a time. Every test follows the updated 2026 TOEFL iBT task types and timing.'
                    : 'Take a complete mock exam under real test conditions, or sharpen one skill at a time. Every test follows the official ' . $label . ' format and timing.',
            ];
        }
        $title = $page === 'mock' ? 'Full Mock Tests' : 'Practice ' . $info['label'];
        if ($page === 'mock') {
            return [
                'title' => $isToefl ? 'TOEFL iBT Full Mock Tests (2026 Format) – ' . $app : $label . ' Full Mock Tests – ' . $app,
                'description' => $isToefl ? 'Complete TOEFL iBT mock tests in the updated 2026 format: every section in the real order and timing.' : 'Complete ' . $label . ' mock tests: every section in the real exam order and timing.',
                'h1' => $isToefl ? 'TOEFL iBT – Full Mock Tests' : $label . ' – Full Mock Tests',
                'intro' => 'Sit a complete ' . ($isToefl ? 'TOEFL iBT' : 'exam') . ': every section in the real order and timing. Finish one section and the next one starts.',
            ];
        }

        return [
            'title' => $isToefl ? 'TOEFL iBT ' . $title . ' (2026 Format) – ' . $app : $label . ' ' . $title . ' – ' . $app,
            'description' => 'Free ' . $label . ' ' . strtolower($info['label']) . ' practice tests ' . ($isToefl ? 'in the 2026 format ' : '') . 'with real ' . ($isToefl ? '' : 'exam ') . 'timing. ' . $info['desc'],
            'h1' => ($isToefl ? 'TOEFL iBT' : $label) . ' – ' . $title,
            'intro' => $info['desc'] . ' Each test has ' . $info['parts'] . ' and takes ' . $info['time'] . '.',
        ];
    }

    /** Everything a page needs: the saved text, or the default when a field is empty. 'content' is the extra text below the tests. */
    public static function resolve(string $group, string $page): array
    {
        $d = self::defaults($group, $page);
        $row = SeoPage::where(['exam_group' => $group, 'page' => $page])->first();

        return [
            'title'       => $row?->meta_title ?: $d['title'],
            'description' => $row?->meta_description ?: $d['description'],
            'h1'          => $row?->h1 ?: $d['h1'],
            'intro'       => $row?->intro ?: $d['intro'],
            'content'     => $row?->content ?: '',
        ];
    }

    /** Landing pages are stored with the group = the exam name (ielts, celpip, toefl). */
    public static function landing(string $exam): array
    {
        $group = ['ielts' => 'ielts-academic', 'celpip' => 'celpip-general', 'toefl' => 'toefl'][$exam];
        $d = self::defaults($group, 'landing');
        $row = SeoPage::where(['exam_group' => $exam, 'page' => 'landing'])->first();

        return ['title' => $row?->meta_title ?: $d['title'], 'description' => $row?->meta_description ?: $d['description']];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Settings the owner edits in the admin panel: logo, footer, social links, contact email (Site Settings)
 * and the home page hero / exam cards / study-method cards (Homepage Content).
 * Anything not saved yet falls back to the text that used to be written directly in the page, so the site looks the same until you change it.
 */
class SiteSetting extends Model
{
    public const LOGO_DIR = 'uploads/site';
    public const DEFAULT_LOGO = 'images/logo-mark.svg';

    protected $fillable = ['key', 'value'];

    private static ?array $stored = null;

    private static function stored(): array
    {
        if (self::$stored === null) {
            self::$stored = [];
            try {
                if (Schema::hasTable('site_settings')) {
                    foreach (self::query()->get() as $row) {
                        self::$stored[$row->key] = json_decode((string) $row->value, true);
                    }
                }
            } catch (\Throwable $e) {
                // before the database update has been run, the defaults are used
            }
        }

        return self::$stored;
    }

    public static function put(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        self::$stored = null;
    }

    /** A saved group of settings; missing top-level fields come from the defaults. */
    public static function group(string $key): array
    {
        $saved = self::stored()[$key] ?? null;
        $defaults = self::defaults()[$key] ?? [];

        if (! is_array($saved)) return $defaults;
        // A blank social link falls back to the default page address.
        $merged = array_replace($defaults, $saved);
        if ($key === 'site') {
            $links = array_filter((array) ($saved['social'] ?? []), fn ($v) => trim((string) $v) !== '');
            $merged['social'] = array_replace((array) ($defaults['social'] ?? []), $links);
        }

        return $merged;
    }

    public static function logoUrl(): string
    {
        $logo = self::group('site')['logo'] ?? null;
        if ($logo && is_file(public_path($logo))) {
            return asset($logo) . '?v=' . filemtime(public_path($logo));
        }

        return asset(self::DEFAULT_LOGO);
    }

    /** "Email us" style addresses, "/about-us", "https://…" and "#" all work as link addresses. */
    public static function href(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || $url === '#') return '#';
        if (preg_match('#^(https?:|mailto:|tel:|//)#i', $url)) return $url;

        return url('/' . ltrim($url, '/'));
    }

    /** Safe heading text where [[words]] become the blue highlighted words. */
    public static function highlight(?string $text): string
    {
        return preg_replace('/\[\[(.+?)\]\]/u', '<span class="hl">$1</span>', e((string) $text));
    }

    public static function defaults(): array
    {
        $r = fn (string $name) => route($name, [], false);

        return [
            'site' => [
                'logo'         => null,
                'footer_about' => 'The right resources and study methods to help you learn a language and reach your target score.',
                'contact_email' => '',
                'social'       => ['linkedin' => 'https://www.linkedin.com/company/ielts-with-ipp/', 'facebook' => 'https://www.facebook.com/profile.php?id=61552949018684', 'tiktok' => 'https://www.tiktok.com/@ielts_with_ipp'],
                'columns'      => [
                    ['title' => 'Company', 'links' => [
                        ['label' => 'About Us', 'url' => $r('about')],
                        ['label' => 'Contact Us', 'url' => $r('contact')],
                        ['label' => 'Privacy Policy', 'url' => $r('privacy')],
                        ['label' => 'Terms and Agreements', 'url' => $r('terms')],
                    ]],
                    ['title' => 'Free Practice', 'links' => [
                        ['label' => 'Take Free IELTS Test', 'url' => '/ielts'],
                        ['label' => 'Take Free CELPIP Test', 'url' => '/celpip'],
                        ['label' => 'Take Free TOEFL Test', 'url' => '/toefl'],
                    ]],
                    ['title' => 'Tools & Tests', 'links' => [
                        ['label' => 'English Level Test', 'url' => $r('level-test.index')],
                        ['label' => 'IELTS Vocabulary', 'url' => $r('vocab.ielts')],
                        ['label' => 'CELPIP Vocabulary', 'url' => $r('vocab.celpip')],
                        ['label' => 'IELTS Score Calculator', 'url' => $r('tools.ielts-calculator')],
                        ['label' => 'IELTS to TOEFL Converter', 'url' => $r('tools.ielts-to-toefl')],
                        ['label' => 'IELTS to PTE Converter', 'url' => $r('tools.ielts-to-pte')],
                        ['label' => 'IELTS to CELPIP Converter', 'url' => $r('tools.ielts-to-celpip')],
                        ['label' => 'IELTS to CEFR Converter', 'url' => $r('tools.ielts-to-cefr')],
                    ]],
                ],
            ],
            'contact' => [
                'manager_name'  => 'Hamza Ahmad',
                'manager_role'  => 'Website Manager',
                'phone'         => '+92 315 4315382',
                'address_line'  => 'P 51, Street 2B, Sajjad Park',
                'city'          => 'Jaranwala',
                'country'       => 'Pakistan',
                'map_query'     => '',          // empty = the address above
                'hours'         => [
                    'Monday' => '24 Hours', 'Tuesday' => '24 Hours', 'Wednesday' => '24 Hours', 'Thursday' => '24 Hours',
                    'Friday' => 'Not available', 'Saturday' => '24 Hours', 'Sunday' => 'Not available',
                ],
            ],
            'home' => [
                'hero' => [
                    'eyebrow' => 'Language learning & exam preparation',
                    'heading' => 'Helping you learn by providing the [[right resources]] and [[study methods]]',
                    'text'    => "Whether you're picking up a new language or aiming for your target band in IELTS or CELPIP, we give you practice tests, clear guides and proven techniques, all in one place.",
                    'button1' => 'Start practising',
                    'button2' => 'Explore study methods',
                    'stats'   => [
                        ['big' => 'IELTS', 'small' => 'Academic & GT'],
                        ['big' => 'CELPIP', 'small' => 'General & LS'],
                        ['big' => 'Free', 'small' => 'practice tests'],
                    ],
                ],
                'exams' => [
                    ['key' => 'ielts', 'title' => 'IELTS', 'text' => 'Academic and General Training practice with band-score guidance, sample essays and speaking questions.', 'button' => 'Practice IELTS'],
                    ['key' => 'celpip', 'title' => 'CELPIP', 'text' => 'CELPIP General and LS practice designed around the real computer-based test format for Canada.', 'button' => 'Practice CELPIP'],
                    ['key' => 'toefl', 'title' => 'TOEFL', 'text' => 'TOEFL iBT practice for study abroad, covering Reading, Listening, Speaking and Writing.', 'button' => 'Practice TOEFL'],
                ],
                'level' => [
                    'title'  => 'English Level Test',
                    'text'   => 'Not sure where you stand? Answer 30 quick questions and find your level from A1 to C2 in about 3 minutes.',
                    'button' => 'Check my level',
                ],
                'methods' => [
                    ['icon' => 'repeat', 'title' => 'Spaced Repetition', 'text' => 'Review new words just before you forget them, so they stay in long-term memory.'],
                    ['icon' => 'brain',  'title' => 'Active Recall',     'text' => 'Test yourself instead of re-reading. Pulling answers from memory makes them stick.'],
                    ['icon' => 'timer',  'title' => 'Pomodoro Sessions', 'text' => 'Study in focused 25-minute blocks with short breaks to keep your energy high.'],
                    ['icon' => 'mic',    'title' => 'Shadowing',         'text' => 'Listen to a native speaker and repeat right after them to improve pronunciation and flow.'],
                    ['icon' => 'clock',  'title' => 'Timed Mock Tests',  'text' => 'Practise under real exam timing so test day feels familiar, not stressful.'],
                    ['icon' => 'pencil', 'title' => 'Feedback Loops',    'text' => 'Write, get feedback, rewrite. Small corrections each week add up to big score gains.'],
                ],
            ],
        ];
    }

    /** Contact details with a few ready-made values (address text, WhatsApp link, map address). */
    public static function contact(): array
    {
        $c = self::group('contact');
        $c['address'] = collect([$c['address_line'] ?? '', $c['city'] ?? '', $c['country'] ?? ''])->filter(fn ($v) => trim((string) $v) !== '')->implode(', ');
        $digits = preg_replace('/\D/', '', (string) ($c['phone'] ?? ''));
        $c['whatsapp'] = $digits !== '' ? 'https://wa.me/' . $digits : null;
        $c['tel'] = $digits !== '' ? 'tel:+' . $digits : null;
        $c['map_src'] = 'https://maps.google.com/maps?q=' . rawurlencode(trim((string) ($c['map_query'] ?? '')) ?: $c['address']) . '&z=15&output=embed';
        $c['map_link'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim((string) ($c['map_query'] ?? '')) ?: $c['address']);

        return $c;
    }
}

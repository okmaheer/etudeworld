<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/** Admin → Site Settings (logo, footer, social links, contact email) and Admin → Homepage Content (hero, exam cards, study-method cards). */
class SiteSettingController extends Controller
{
    public const ICONS = ['repeat' => 'Repeat arrows', 'brain' => 'Brain', 'timer' => 'Timer', 'mic' => 'Microphone', 'clock' => 'Checklist', 'pencil' => 'Pencil'];

    public function site()
    {
        return view('admin.site-settings.site', ['s' => SiteSetting::group('site')]);
    }

    public function saveSite(Request $request)
    {
        $request->validate([
            'logo'          => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'footer_about'  => ['nullable', 'string', 'max:400'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'social.*'      => ['nullable', 'string', 'max:300'],
            'columns'       => ['nullable', 'array', 'max:6'],
            'columns.*.title'        => ['nullable', 'string', 'max:60'],
            'columns.*.links'        => ['nullable', 'array', 'max:20'],
            'columns.*.links.*.label' => ['nullable', 'string', 'max:80'],
            'columns.*.links.*.url'   => ['nullable', 'string', 'max:300'],
        ], ['logo.max' => 'The logo must be smaller than 2 MB.']);

        $old = SiteSetting::group('site');
        $logo = $old['logo'] ?? null;
        if ($request->hasFile('logo')) {
            $this->deleteLogo($logo);
            $file = $request->file('logo');
            $name = 'logo-' . substr(md5(uniqid('', true)), 0, 8) . '.' . ($file->extension() ?: 'png');
            $file->move(public_path(SiteSetting::LOGO_DIR), $name);
            $logo = SiteSetting::LOGO_DIR . '/' . $name;
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($logo);
            $logo = null;
        }

        $columns = [];
        foreach ((array) $request->input('columns', []) as $c) {
            $links = [];
            foreach ((array) ($c['links'] ?? []) as $l) {
                $label = trim((string) ($l['label'] ?? ''));
                if ($label === '') continue;
                $links[] = ['label' => $label, 'url' => trim((string) ($l['url'] ?? ''))];
            }
            $title = trim((string) ($c['title'] ?? ''));
            if ($title === '' && ! $links) continue;
            $columns[] = ['title' => $title, 'links' => $links];
        }

        $social = [];
        foreach (['linkedin', 'facebook', 'tiktok'] as $k) {
            $social[$k] = trim((string) $request->input('social.' . $k, ''));
        }

        SiteSetting::put('site', [
            'logo'          => $logo,
            'footer_about'  => trim((string) $request->input('footer_about', '')),
            'contact_email' => trim((string) $request->input('contact_email', '')),
            'social'        => $social,
            'columns'       => $columns,
        ]);

        return redirect()->route('admin.site-settings.site')->with('success', 'Site settings were saved.');
    }

    public function contact()
    {
        return view('admin.site-settings.contact', ['c' => SiteSetting::group('contact')]);
    }

    public function saveContact(Request $request)
    {
        $request->validate([
            'manager_name' => ['nullable', 'string', 'max:120'], 'manager_role' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]*$/'],
            'address_line' => ['nullable', 'string', 'max:200'], 'city' => ['nullable', 'string', 'max:100'], 'country' => ['nullable', 'string', 'max:100'],
            'map_query' => ['nullable', 'string', 'max:250'], 'hours' => ['nullable', 'array'], 'hours.*' => ['nullable', 'string', 'max:60'],
        ], ['phone.regex' => 'The number can only have digits, spaces, + and -.']);
        $d = SiteSetting::defaults()['contact'];
        $t = fn ($k) => trim((string) $request->input($k, ''));
        $hours = [];
        foreach ($d['hours'] as $day => $def) {
            $hours[$day] = trim((string) $request->input('hours.' . $day, '')) ?: $def;
        }
        SiteSetting::put('contact', [
            'manager_name' => $t('manager_name'), 'manager_role' => $t('manager_role'), 'phone' => $t('phone'),
            'address_line' => $t('address_line'), 'city' => $t('city'), 'country' => $t('country'), 'map_query' => $t('map_query'), 'hours' => $hours,
        ]);

        return redirect()->route('admin.site-settings.contact')->with('success', 'The contact details were saved.');
    }

    public function home()
    {
        return view('admin.site-settings.home', ['h' => SiteSetting::group('home'), 'icons' => self::ICONS]);
    }

    public function saveHome(Request $request)
    {
        $request->validate([
            'hero.eyebrow' => ['nullable', 'string', 'max:120'],
            'hero.heading' => ['nullable', 'string', 'max:220'],
            'hero.text'    => ['nullable', 'string', 'max:500'],
            'hero.button1' => ['nullable', 'string', 'max:40'],
            'hero.button2' => ['nullable', 'string', 'max:40'],
            'hero.stats'   => ['nullable', 'array', 'max:3'],
            'exams'        => ['nullable', 'array', 'max:3'],
            'level'        => ['nullable', 'array'],
            'methods'      => ['nullable', 'array', 'max:12'],
            'methods.*.icon' => ['nullable', 'in:' . implode(',', array_keys(self::ICONS))],
        ]);
        $d = SiteSetting::defaults()['home'];
        $t = fn ($v, $fallback = '') => trim((string) ($v ?? $fallback));

        $hero = (array) $request->input('hero', []);
        $stats = [];
        foreach ((array) ($hero['stats'] ?? []) as $s) {
            if ($t($s['big'] ?? '') === '' && $t($s['small'] ?? '') === '') continue;
            $stats[] = ['big' => $t($s['big'] ?? ''), 'small' => $t($s['small'] ?? '')];
        }

        $exams = [];
        foreach ($d['exams'] as $i => $def) {
            $e = (array) $request->input('exams.' . $i, []);
            $exams[] = ['key' => $def['key'], 'title' => $t($e['title'] ?? '', $def['title']), 'text' => $t($e['text'] ?? ''), 'button' => $t($e['button'] ?? '', $def['button'])];
        }

        $methods = [];
        foreach ((array) $request->input('methods', []) as $m) {
            $title = $t($m['title'] ?? '');
            if ($title === '') continue;
            $methods[] = ['icon' => $m['icon'] ?? 'pencil', 'title' => $title, 'text' => $t($m['text'] ?? '')];
        }

        $lv = (array) $request->input('level', []);
        SiteSetting::put('home', [
            'hero' => [
                'eyebrow' => $t($hero['eyebrow'] ?? ''), 'heading' => $t($hero['heading'] ?? '', $d['hero']['heading']),
                'text' => $t($hero['text'] ?? ''), 'button1' => $t($hero['button1'] ?? '', $d['hero']['button1']),
                'button2' => $t($hero['button2'] ?? '', $d['hero']['button2']), 'stats' => $stats,
            ],
            'exams'   => $exams,
            'level'   => ['title' => $t($lv['title'] ?? '', $d['level']['title']), 'text' => $t($lv['text'] ?? ''), 'button' => $t($lv['button'] ?? '', $d['level']['button'])],
            'methods' => $methods,
        ]);

        return redirect()->route('admin.site-settings.home')->with('success', 'The home page was updated.');
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && str_starts_with($path, SiteSetting::LOGO_DIR)) {
            File::delete(public_path($path));
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoPage;
use App\Support\SeoPages;
use App\Support\SitePages;
use Illuminate\Http\Request;

/** Admin → SEO Pages: title, description, heading and text of the exam list pages. */
class SeoPageController extends Controller
{
    public function index(Request $request)
    {
        $groups = SeoPages::groups();
        $tab = $request->query('tab', 'ielts-academic');
        $siteSections = SitePages::sections();
        $isSite = str_starts_with($tab, 'site-');
        abort_unless($tab === 'home' || isset($groups[$tab]) || ($isSite && isset($siteSections[substr($tab, 5)])), 404);

        // The rows of this tab: [ [group, page, label, url, defaults, saved] ... ]
        $rows = [];
        if ($tab === 'home') {
            foreach (SeoPages::landings() as $exam => $l) {
                $group = ['ielts' => 'ielts-academic', 'celpip' => 'celpip-general', 'toefl' => 'toefl'][$exam];
                $rows[] = ['group' => $exam, 'page' => 'landing', 'label' => $l['label'], 'url' => $l['path'],
                    'defaults' => SeoPages::defaults($group, 'landing'), 'full' => false];
            }
        } elseif ($isSite) {
            foreach (SitePages::section(substr($tab, 5)) as $p) {
                $rows[] = ['group' => SitePages::GROUP, 'page' => $p['path'], 'label' => $p['label'], 'url' => $p['path'], 'defaults' => ['title' => '', 'description' => '', 'h1' => '', 'intro' => ''], 'full' => empty($p['part']), 'part' => $p['part'] ?? null, 'fetch' => true];
            }
        } else {
            foreach (array_merge(['hub', 'mock'], $groups[$tab]['modules']) as $page) {
                $rows[] = ['group' => $tab, 'page' => $page, 'label' => SeoPages::PAGES[$page], 'url' => SeoPages::url($tab, $page),
                    'defaults' => SeoPages::defaults($tab, $page), 'full' => true];
            }
        }
        $saved = SeoPage::whereIn('exam_group', array_unique(array_column($rows, 'group')))->get()
            ->keyBy(fn ($r) => $r->exam_group . '|' . $r->page);

        return view('admin.seo.index', compact('tab', 'groups', 'rows', 'saved', 'siteSections'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'tab'                          => ['required', 'string'],
            'pages'                        => ['array'],
            'pages.*.*.meta_title'         => ['nullable', 'string', 'max:255'],
            'pages.*.*.meta_description'   => ['nullable', 'string', 'max:500'],
            'pages.*.*.h1'                 => ['nullable', 'string', 'max:255'],
            'pages.*.*.intro'              => ['nullable', 'string', 'max:2000'],
            'pages.*.*.content'            => ['nullable', 'string', 'max:200000'],
        ]);

        $allowedGroups = array_merge(array_keys(SeoPages::groups()), array_keys(SeoPages::landings()), [SitePages::GROUP]);
        $sitePaths = array_keys(SitePages::all());
        foreach ($data['pages'] ?? [] as $group => $pages) {
            if (! in_array($group, $allowedGroups, true)) {
                continue;
            }
            foreach ($pages as $page => $f) {
                if ($group === SitePages::GROUP ? ! in_array($page, $sitePaths, true) : ! isset(SeoPages::PAGES[$page])) {
                    continue;
                }
                $values = array_map(fn ($v) => ($v = trim((string) $v)) === '' ? null : $v, array_intersect_key($f, array_flip(['meta_title', 'meta_description', 'h1', 'intro', 'content'])));
                // An editor that was never opened sends nothing – keep what was saved. An empty editor sends "<p></p>"-like text.
                if (isset($values['content']) && trim(strip_tags($values['content'], '<img><iframe><table>')) === '') {
                    $values['content'] = null;
                }
                if (! array_filter($values)) {
                    SeoPage::where(['exam_group' => $group, 'page' => $page])->delete();
                } else {
                    SeoPage::updateOrCreate(['exam_group' => $group, 'page' => $page], $values + ['meta_title' => null, 'meta_description' => null, 'h1' => null, 'intro' => null, 'content' => null]);
                }
            }
        }

        return redirect()->route('admin.seo.index', ['tab' => $data['tab']])->with('success', 'SEO settings were saved.');
    }
}

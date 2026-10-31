<?php

namespace App\Support;

/**
 * The "Sample Answers" menu tree. Used for BOTH the navigation bar and the
 * page addresses, so adding an item here adds it everywhere.
 *
 *   /sample-answers
 *   /sample-answers/ielts/speaking/part-1
 *   /sample-answers/celpip/writing/task-2   … and so on
 *
 * Route names follow the address: sample.ielts.speaking.part-1
 * IELTS / CELPIP / Speaking / Writing are only menu labels, not pages.
 */
class SampleAnswers
{
    public static function tree(): array
    {
        $tasks = fn (int $n, string $word, string $prefix = '') => array_map(
            fn ($i) => ['slug' => strtolower($word) . '-' . $i, 'label' => $prefix . $word . ' ' . $i],
            range(1, $n)
        );

        return [
            'slug'  => 'sample-answers',
            'label' => 'Sample Answers',
            'children' => [
                [
                    'slug' => 'ielts', 'label' => 'IELTS',
                    'children' => [
                        ['slug' => 'speaking', 'label' => 'Speaking', 'children' => $tasks(3, 'Part')],
                        ['slug' => 'writing',  'label' => 'Writing',  'children' => $tasks(2, 'Task', 'Writing ')],
                    ],
                ],
                [
                    'slug' => 'celpip', 'label' => 'CELPIP',
                    'children' => [
                        ['slug' => 'speaking', 'label' => 'Speaking', 'children' => $tasks(8, 'Task')],
                        ['slug' => 'writing',  'label' => 'Writing',  'children' => $tasks(2, 'Task', 'Writing ')],
                    ],
                ],
            ],
        ];
    }

    /**
     * Every page in the tree as [routeName, uri, pageTitle].
     * e.g. ['sample.ielts.speaking.part-1', 'sample-answers/ielts/speaking/part-1', 'IELTS Speaking Part 1 Sample Answers']
     */
    public static function pages(): array
    {
        $out = [];
        $walk = function (array $node, array $slugs, array $names, array $labels) use (&$walk, &$out) {
            $slugs[] = $node['slug'];
            $labels[] = $node['label'];
            $routeName = implode('.', $names);
            $readable = array_slice($labels, 1);                     // drop "Sample Answers"
            $title = $readable ? implode(' ', self::dedupe($readable)) . ' Sample Answers' : 'Sample Answers';
            // Only the top page and the final items (Part 1, Task 2…) are pages.
            // In-between levels (IELTS, CELPIP, Speaking, Writing) are just menu labels.
            $isLeaf = empty($node['children']);
            if ($isLeaf || count($slugs) === 1) {
                $out[] = [$routeName, implode('/', $slugs), $title];
            }
            foreach ($node['children'] ?? [] as $child) {
                $walk($child, $slugs, array_merge($names, [$child['slug']]), $labels);
            }
        };
        $walk(self::tree(), [], ['sample'], []);

        return $out;
    }

    /**
     * The final items (Part 1, Task 2…) as blog categories, so a blog post can be filed under one.
     *   key   => 'sa-ielts-speaking-part-1'           (saved in the blogs table)
     *   label => 'IELTS › Speaking › Part 1'          (shown in the admin panel)
     *   path  => 'sample-answers/ielts/speaking/part-1' (page that lists these posts)
     *   title => 'IELTS Speaking Part 1 Sample Answers'
     */
    public static function categories(): array
    {
        $out = [];
        $walk = function (array $node, array $slugs, array $labels) use (&$walk, &$out) {
            if (empty($node['children'])) {
                $readable = self::dedupe($labels);
                $out['sa-' . implode('-', $slugs)] = [
                    'label' => implode(' › ', $labels),
                    'path'  => 'sample-answers/' . implode('/', $slugs),
                    'title' => implode(' ', $readable) . ' Sample Answers',
                    'route' => 'sample.' . implode('.', $slugs),
                ];
                return;
            }
            foreach ($node['children'] as $child) {
                $walk($child, array_merge($slugs, [$child['slug']]), array_merge($labels, [$child['label']]));
            }
        };
        foreach (self::tree()['children'] as $top) {
            $walk($top, [$top['slug']], [$top['label']]);
        }

        return $out;
    }

    /**
     * Hub pages: one per exam + skill (IELTS Speaking, IELTS Writing, CELPIP Speaking, CELPIP Writing).
     * Each lists all its parts / tasks. Keyed by route name, e.g. 'sample.ielts.speaking'.
     */
    public static function hubs(): array
    {
        $out = [];
        foreach (self::tree()['children'] as $exam) {
            foreach ($exam['children'] as $skill) {
                $items = [];
                foreach ($skill['children'] as $item) {
                    $key = 'sa-' . $exam['slug'] . '-' . $skill['slug'] . '-' . $item['slug'];
                    $items[] = ['label' => $item['label'], 'key' => $key, 'path' => 'sample-answers/' . $exam['slug'] . '/' . $skill['slug'] . '/' . $item['slug']];
                }
                $out['sample.' . $exam['slug'] . '.' . $skill['slug']] = [
                    'path'  => 'sample-answers/' . $exam['slug'] . '/' . $skill['slug'],
                    'title' => $exam['label'] . ' ' . $skill['label'] . ' Sample Answers',
                    'exam'  => $exam['label'],
                    'skill' => $skill['label'],
                    'items' => $items,
                ];
            }
        }

        return $out;
    }

    /** Navigation tree with the route name of every item filled in. */
    public static function menu(): array
    {
        $fill = function (array $node, string $name) use (&$fill) {
            $node['route'] = $name;
            $node['children'] = array_map(fn ($c) => $fill($c, $name . '.' . $c['slug']), $node['children'] ?? []);
            return $node;
        };

        return $fill(self::tree(), 'sample');
    }

    /** "Writing Writing Task 1" → "Writing Task 1" */
    private static function dedupe(array $labels): array
    {
        $words = [];
        foreach (explode(' ', implode(' ', $labels)) as $w) {
            if (end($words) !== $w) $words[] = $w;
        }
        return $words;
    }
}

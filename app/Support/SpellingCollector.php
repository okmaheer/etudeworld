<?php

namespace App\Support;

use App\Models\IeltsTest;
use App\Models\SpellingWord;

/**
 * Collects the answers of IELTS Listening Part 1 and Part 4 (fill-in-the-blank types) as "important spellings".
 * Numbers, dates, very easy words and duplicates are left out. The admin ticks which words are shown to students.
 */
class SpellingCollector
{
    private const TYPES = ['fitb', 'fitb_para', 'fitb_flow', 'fitb_table'];

    private const EASY = ['about', 'after', 'again', 'also', 'and', 'any', 'are', 'ask', 'back', 'bad', 'bag', 'because', 'been', 'before', 'best', 'big', 'book', 'both', 'bus', 'but', 'buy', 'call', 'can', 'car', 'card', 'come', 'cost', 'day', 'did', 'door', 'down', 'each', 'easy', 'end', 'every', 'fast', 'find', 'first', 'for', 'free', 'from', 'get', 'give', 'good', 'great', 'had', 'has', 'have', 'help', 'here', 'home', 'house', 'how', 'idea', 'into', 'its', 'just', 'know', 'last', 'late', 'like', 'little', 'long', 'look', 'make', 'many', 'more', 'most', 'much', 'name', 'new', 'next', 'not', 'now', 'off', 'old', 'one', 'only', 'open', 'other', 'our', 'out', 'over', 'own', 'pay', 'people', 'place', 'play', 'room', 'room', 'same', 'see', 'she', 'shop', 'some', 'start', 'stop', 'take', 'tell', 'than', 'that', 'the', 'their', 'them', 'then', 'there', 'they', 'this', 'time', 'too', 'two', 'use', 'very', 'want', 'was', 'water', 'way', 'week', 'well', 'were', 'what', 'when', 'where', 'which', 'who', 'will', 'with', 'work', 'year', 'you', 'your',
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday', 'january', 'february', 'march', 'april', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];

    public const CATEGORIES = ['Nouns', 'Verbs', 'Adjectives', 'Adverbs', 'Gerunds (-ing words)', 'Places', 'Names', 'Phrases'];

    private const PLACE_WORDS = ['street', 'road', 'avenue', 'lane', 'drive', 'park', 'square', 'centre', 'center', 'library', 'museum', 'station', 'airport', 'hospital', 'hotel', 'school', 'university', 'college', 'bridge', 'beach', 'island', 'lake', 'river', 'mountain', 'garden', 'gardens', 'market', 'church', 'cafe', 'theatre', 'theater', 'stadium', 'gallery', 'harbour', 'harbor', 'city', 'town', 'village', 'hall', 'court', 'crescent', 'terrace', 'gate', 'hill', 'valley', 'bay', 'point', 'cinema', 'pool', 'gym'];

    private static ?array $dict = null;

    /** The free English word list the site already has for spell checking: [common words => flags, proper nouns => true]. */
    private static function dict(): array
    {
        if (self::$dict !== null) return self::$dict;
        $common = $proper = [];
        $file = public_path('vendor/spellcheck/en_US.dic');
        foreach (is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : [] as $line) {
            [$w, $flags] = array_pad(explode('/', $line, 2), 2, '');
            if ($w === '' || ctype_digit($w)) continue;
            if (preg_match('/^\p{Lu}/u', $w)) $proper[mb_strtolower($w)] = true;
            else $common[$w] = ($common[$w] ?? '') . $flags;
        }

        return self::$dict = ['common' => $common, 'proper' => $proper];
    }

    /** Free automatic grouping: looks the word up in the English word list and reads its endings. The admin can change any group. */
    public static function guess(string $word): string
    {
        $word = trim($word);
        $parts = explode(' ', mb_strtolower($word));
        $last = end($parts);
        $cap = (bool) preg_match('/^\p{Lu}/u', $word);
        if (in_array($last, self::PLACE_WORDS, true) && count($parts) > 1) return 'Places';
        if (count($parts) > 1) return $cap ? 'Names' : 'Phrases';
        $w = $parts[0];
        if (in_array($w, self::PLACE_WORDS, true)) return 'Places';
        $d = self::dict();
        $flags = $d['common'][$w] ?? null;
        if ($flags === null) {
            if (isset($d['proper'][$w]) || $cap) return 'Names';                      // not an ordinary English word
        } else {
            if (preg_match('/(ly)$/', $w) && mb_strlen($w) > 4) {
                $base = mb_substr($w, 0, -2);
                if (isset($d['common'][$base]) || isset($d['common'][mb_substr($base, 0, -1) . 'y']) || isset($d['common'][$base . 'le'])) return 'Adverbs';
            }
            if (preg_match('/ing$/', $w) && mb_strlen($w) > 5) return 'Gerunds (-ing words)';
            if (preg_match('/[TY]/', $flags)) return 'Adjectives';                    // can take -er / -est / -ly / -ness
            if (str_contains($flags, 'D') && str_contains($flags, 'G') && ! str_contains($flags, 'M')) return 'Verbs';   // takes -ed and -ing but no possessive: a verb
            return 'Nouns';
        }
        if (preg_match('/ly$/', $w) && mb_strlen($w) > 4) return 'Adverbs';
        if (preg_match('/ing$/', $w) && mb_strlen($w) > 5) return 'Gerunds (-ing words)';
        if (preg_match('/(ous|ful|less|able|ible|ical|ish|ive)$/', $w)) return 'Adjectives';
        if (preg_match('/(ize|ise|ify)$/', $w) && mb_strlen($w) > 5) return 'Verbs';

        return 'Nouns';
    }

    /** Clean one answer into words (first = main spelling, others = accepted alternatives), or null if it is not useful. */
    public static function clean(string $answer): ?array
    {
        $parts = [];
        foreach (explode('/', html_entity_decode(strip_tags($answer))) as $p) {
            $p = trim(preg_replace('/\s+/u', ' ', $p), " \t\n\r.,;:!?\"'()");
            if ($p !== '') $parts[] = $p;
        }
        if (! $parts) return null;
        $main = $parts[0];
        if (preg_match('/\d/', $main)) return null;                                    // numbers, dates, prices, postcodes
        if (mb_strlen(preg_replace('/[^\pL]/u', '', $main)) < 4) return null;          // too short to matter
        $words = explode(' ', $main);
        if (count($words) > 3) return null;
        $hard = array_filter($words, fn ($w) => ! in_array(mb_strtolower($w), self::EASY, true) && mb_strlen($w) >= 3);
        if (! $hard) return null;                                                      // only easy words

        return ['word' => $main, 'alts' => array_slice($parts, 1)];
    }

    /** The cleaned words from Part 1 and Part 4 of one test, keyed by lower-case word. */
    public static function candidates(IeltsTest $test): array
    {
        $content = IeltsListening::normalize($test->moduleContent('listening'));
        $found = [];
        foreach ($content['groups'] as $g) {
            if (! in_array($g['passage'], [1, 4], true) || ! in_array($g['type'], self::TYPES, true)) continue;
            $answers = [];
            if ($g['type'] === 'fitb_table') {
                $answers = (array) ($g['answers'] ?? []);
            } else {
                foreach ((array) ($g['items'] ?? []) as $item) $answers = array_merge($answers, (array) ($item['answers'] ?? []));
            }
            foreach ($answers as $a) {
                $c = self::clean((string) $a);
                if ($c) $found[mb_strtolower($c['word'])] ??= $c;
            }
        }

        return $found;
    }

    /** Update the collected words after a Listening test was saved. */
    public static function sync(IeltsTest $test): void
    {
        $found = self::candidates($test);
        foreach (SpellingWord::all() as $w) {
            $tests = (array) $w->tests;
            if (! in_array($test->id, $tests, true) || isset($found[$w->wkey])) continue;
            $tests = array_values(array_diff($tests, [$test->id]));
            $tests ? $w->update(['tests' => $tests]) : $w->delete();
        }
        foreach ($found as $key => $c) {
            $w = SpellingWord::firstOrNew(['wkey' => mb_substr($key, 0, 120)]);
            if (! $w->exists) {
                $w->word = mb_substr($c['word'], 0, 120);
                $w->category = self::guess($c['word']);
            }
            $w->tests = array_values(array_unique(array_merge((array) $w->tests, [$test->id])));
            $w->alternatives = array_values(array_unique(array_merge((array) $w->alternatives, $c['alts'])));
            $w->save();
        }
    }

    public static function rescanAll(): void
    {
        foreach (IeltsTest::all() as $t) self::sync($t);
        // words still in an old group name get a new guess
        SpellingWord::whereIn('category', ['General words', 'Names and places'])->get()->each(fn ($w) => $w->update(['category' => self::guess($w->word)]));
    }
}

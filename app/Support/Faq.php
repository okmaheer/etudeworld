<?php

namespace App\Support;

/** FAQ blocks made in the text editor → the "FAQPage" search-engine data (schema.org), made automatically. */
class Faq
{
    /** [['q' => question, 'a' => answer], …] from every FAQ block in $html. */
    public static function extract(string $html): array
    {
        if (! str_contains($html, 'ew-faq__item')) {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $faqs = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ew-faq__item ")]') as $item) {
            $q = $xpath->query('.//*[contains(@class, "ew-faq__q")]', $item)->item(0);
            $a = $xpath->query('.//*[contains(@class, "ew-faq__a")]', $item)->item(0);
            if ($q && $a && trim($q->textContent) !== '') {
                $faqs[] = ['q' => trim($q->textContent), 'a' => trim(preg_replace('/\s+/', ' ', $a->textContent))];
            }
        }

        return $faqs;
    }

    /** The <script type="application/ld+json"> tag for $html, or '' when it has no FAQ. */
    public static function jsonLd(string $html): string
    {
        $faqs = self::extract($html);
        if (! $faqs) {
            return '';
        }

        return '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']]], $faqs),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
    }
}

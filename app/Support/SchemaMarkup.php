<?php

namespace App\Support;

use App\Models\SiteSetting;

/** schema.org "structured data" for search engines: Organization, WebSite, AboutPage and ContactPage. */
class SchemaMarkup
{
    public static function organization(): array
    {
        $site = SiteSetting::group('site');
        $c = SiteSetting::contact();
        $same = array_values(array_filter(array_map('trim', (array) ($site['social'] ?? [])), fn ($u) => preg_match('#^https?://#i', $u)));
        $logo = strtok(SiteSetting::logoUrl(), '?');

        $org = [
            '@type' => 'Organization',
            '@id'   => url('/') . '#organization',
            'name'  => config('app.name'),
            'url'   => url('/'),
            'logo'  => ['@type' => 'ImageObject', 'url' => $logo],
            'description' => $site['footer_about'] ?? null,
            'address' => ['@type' => 'PostalAddress', 'streetAddress' => $c['address_line'] ?? '', 'addressLocality' => $c['city'] ?? '', 'addressCountry' => $c['country'] ?? ''],
        ];
        if ($same) $org['sameAs'] = $same;
        $point = ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'availableLanguage' => ['English']];
        if (filled($c['phone'] ?? null)) $point['telephone'] = $c['phone'];
        if (filled($site['contact_email'] ?? null)) $point['email'] = $site['contact_email'];
        if (count($point) > 3) $org['contactPoint'] = $point;

        return array_filter($org, fn ($v) => $v !== null && $v !== '');
    }

    public static function website(): array
    {
        return ['@type' => 'WebSite', '@id' => url('/') . '#website', 'url' => url('/'), 'name' => config('app.name'), 'publisher' => ['@id' => url('/') . '#organization']];
    }

    /** Organization + WebSite (home page). */
    public static function home(): string
    {
        return self::tag([self::organization(), self::website()]);
    }

    /** @param string $type AboutPage | ContactPage */
    public static function page(string $type, string $name, string $description): string
    {
        return self::tag([
            [
                '@type' => $type, '@id' => url()->current() . '#webpage', 'url' => url()->current(), 'name' => $name, 'description' => $description,
                'isPartOf' => ['@id' => url('/') . '#website'], 'about' => ['@id' => url('/') . '#organization'],
            ],
            self::organization(),
            self::website(),
        ]);
    }

    private static function tag(array $graph): string
    {
        return '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
    }
}

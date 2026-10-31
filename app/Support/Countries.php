<?php

namespace App\Support;

/** Countries with their phone codes, for the "Get a Quote" form. */
class Countries
{
    public const LIST = [
        'Pakistan' => '+92', 'Canada' => '+1', 'United States' => '+1', 'United Kingdom' => '+44', 'India' => '+91', 'Bangladesh' => '+880',
        'United Arab Emirates' => '+971', 'Saudi Arabia' => '+966', 'Qatar' => '+974', 'Kuwait' => '+965', 'Oman' => '+968', 'Bahrain' => '+973',
        'Australia' => '+61', 'New Zealand' => '+64', 'Ireland' => '+353', 'Nepal' => '+977', 'Sri Lanka' => '+94', 'Afghanistan' => '+93',
        'Philippines' => '+63', 'Indonesia' => '+62', 'Malaysia' => '+60', 'Singapore' => '+65', 'China' => '+86', 'Hong Kong' => '+852',
        'Japan' => '+81', 'South Korea' => '+82', 'Vietnam' => '+84', 'Thailand' => '+66', 'Iran' => '+98', 'Iraq' => '+964', 'Jordan' => '+962',
        'Lebanon' => '+961', 'Turkey' => '+90', 'Egypt' => '+20', 'Morocco' => '+212', 'Algeria' => '+213', 'Tunisia' => '+216',
        'Nigeria' => '+234', 'Ghana' => '+233', 'Kenya' => '+254', 'Uganda' => '+256', 'Ethiopia' => '+251', 'South Africa' => '+27',
        'Germany' => '+49', 'France' => '+33', 'Italy' => '+39', 'Spain' => '+34', 'Portugal' => '+351', 'Netherlands' => '+31',
        'Russia' => '+7', 'Ukraine' => '+380', 'Poland' => '+48', 'Brazil' => '+55', 'Mexico' => '+52', 'Colombia' => '+57',
    ];

    public static function names(): array
    {
        return array_keys(self::LIST);
    }

    public static function dial(string $country): ?string
    {
        return self::LIST[$country] ?? null;
    }
}

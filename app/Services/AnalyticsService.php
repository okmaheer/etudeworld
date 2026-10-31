<?php

namespace App\Services;

/**
 * Website visitor numbers for the admin dashboard.
 *
 * Google Analytics 4 is NOT connected yet, so every number returns null
 * (the dashboard then shows "—"). When we connect GA4 later, only this
 * file needs to change. Settings will go in the .env file:
 *   GA4_PROPERTY_ID=123456789
 *   GA4_CREDENTIALS=storage/app/private/ga4-service-account.json
 */
class AnalyticsService
{
    public function isConnected(): bool
    {
        return false;
    }

    /** People on the website right now (GA4 "realtime" report). */
    public function liveVisitors(): ?int
    {
        return null;
    }

    /** Visitors today. */
    public function visitorsToday(): ?int
    {
        return null;
    }

    /** Visitors this month. */
    public function visitorsThisMonth(): ?int
    {
        return null;
    }
}

<?php

namespace App\Support;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;

/**
 * Who may open the PAID tests of an exam?
 *   • a logged-in admin, always;
 *   • a logged-in student whose email was confirmed by Google / Microsoft AND who has a paid-client record
 *     for that exam that is active right now (checked in the database on every request – nothing is remembered).
 * Everyone else (not logged in, logged in but not paid, or expired) sees the lock.
 */
class PaidAccess
{
    private static array $cache = [];

    public static function allows(string $exam): bool
    {
        if (Auth::check()) return true;                                  // admin
        $student = Auth::guard('student')->user();
        if (! $student || ! $student->email_trusted) return false;

        $key = $student->id . '|' . $student->email . '|' . $exam;
        return self::$cache[$key] ??= Client::active()->where('exam', $exam)->where('email', mb_strtolower(trim($student->email)))->exists();
    }

    /** Why is it locked? guest | none | expired (with the end date), for the message on the lock screen. */
    public static function reason(string $exam): array
    {
        $student = Auth::guard('student')->user();
        if (! $student) return ['state' => 'guest'];
        if (! $student->email_trusted) return ['state' => 'none'];
        $last = Client::where('exam', $exam)->where('email', mb_strtolower(trim($student->email)))->orderByDesc('ends_at')->first();

        return $last && $last->ends_at < now() ? ['state' => 'expired', 'ended' => $last->ends_at] : ['state' => 'none'];
    }

    /** Short text for the lock screen / lock page. */
    public static function message(string $exam): string
    {
        $name = Client::EXAMS[$exam] ?? strtoupper($exam);
        $r = self::reason($exam);

        return match ($r['state']) {
            'guest'   => 'Log in with the email address of your paid account to unlock this test.',
            'expired' => 'Your ' . $name . ' access ended on ' . $r['ended']->format('j M Y') . '. Purchase a plan to renew it.',
            default   => 'Your account does not have ' . $name . ' premium access yet. Purchase a plan to unlock all ' . $name . ' tests.',
        };
    }
}

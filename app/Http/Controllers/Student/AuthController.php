<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Student sign-in with Google or Microsoft (no passwords).
 *
 * How it works: the student clicks "Continue with Google/Microsoft" → they sign in on Google's/Microsoft's
 * own page → they are sent back to /login/{provider}/callback with a one-time code → we swap the code for
 * their name and email → we find or create the Student and log them in.
 *
 * Needs keys in .env (see config/services.php). Until they exist, the buttons say "not set up yet",
 * and on your own computer (APP_ENV=local) a simple test sign-in is shown instead.
 */
class AuthController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft'];

    public function show(Request $request)
    {
        if (Auth::guard('student')->check()) {
            return redirect($this->intended($request));
        }
        if ($request->filled('return')) {
            $request->session()->put('student.intended', $this->safeReturn($request->query('return')));
        }

        return view('student.login', [
            'google'    => self::configured('google'),
            'microsoft' => self::configured('microsoft'),
            'testLogin' => (bool) config('services.student_test_login') && app()->environment('local'),
            'reason'    => $request->query('reason'),
        ]);
    }

    public static function configured(string $provider): bool
    {
        return filled(config("services.$provider.client_id")) && filled(config("services.$provider.client_secret"));
    }

    /** Step 1: send the student to Google / Microsoft. */
    public function redirect(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        if (! self::configured($provider)) {
            return redirect()->route('student.login')->with('error', ucfirst($provider) . ' sign-in is not set up yet.');
        }
        $state = Str::random(40);
        $request->session()->put('student.oauth_state', $state);
        $c = config("services.$provider");
        $params = [
            'client_id'     => $c['client_id'],
            'redirect_uri'  => route('student.callback', $provider),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'prompt'        => 'select_account',
        ];
        $url = $provider === 'google'
            ? 'https://accounts.google.com/o/oauth2/v2/auth'
            : 'https://login.microsoftonline.com/' . ($c['tenant'] ?: 'common') . '/oauth2/v2.0/authorize';
        if ($provider === 'microsoft') $params['scope'] .= ' User.Read';

        return redirect()->away($url . '?' . http_build_query($params));
    }

    /** Step 2: Google / Microsoft sends the student back here. */
    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);
        $fail = fn (string $msg) => redirect()->route('student.login')->with('error', $msg);

        if ($request->filled('error')) return $fail('Sign-in was cancelled.');
        if (! $request->filled('code') || $request->query('state') !== $request->session()->pull('student.oauth_state')) {
            return $fail('Sign-in could not be completed. Please try again.');
        }

        $c = config("services.$provider");
        $tokenUrl = $provider === 'google'
            ? 'https://oauth2.googleapis.com/token'
            : 'https://login.microsoftonline.com/' . ($c['tenant'] ?: 'common') . '/oauth2/v2.0/token';
        $token = Http::asForm()->post($tokenUrl, [
            'client_id'     => $c['client_id'],
            'client_secret' => $c['client_secret'],
            'code'          => $request->query('code'),
            'redirect_uri'  => route('student.callback', $provider),
            'grant_type'    => 'authorization_code',
        ]);
        if (! $token->ok() || ! $token->json('access_token')) return $fail('Sign-in could not be completed. Please try again.');
        $access = $token->json('access_token');

        if ($provider === 'google') {
            $u = Http::withToken($access)->get('https://openidconnect.googleapis.com/v1/userinfo');
            $email = $u->json('email');
            $verified = (bool) $u->json('email_verified');
            $trusted = $verified;                                    // Google confirmed this address
            $profile = ['name' => $u->json('name'), 'id' => $u->json('sub'), 'avatar' => $u->json('picture')];
        } else {
            $u = Http::withToken($access)->get('https://graph.microsoft.com/v1.0/me');
            $email = $u->json('mail') ?: $u->json('userPrincipalName');
            $verified = true;
            // Microsoft's "mail" field can be typed in by the account owner, so it is trusted for PAID access only
            // when it is the same as the sign-in name (userPrincipalName), which Microsoft does check.
            $upn = strtolower((string) $u->json('userPrincipalName'));
            $trusted = filter_var($upn, FILTER_VALIDATE_EMAIL) && strtolower((string) $email) === $upn;
            $profile = ['name' => $u->json('displayName'), 'id' => $u->json('id'), 'avatar' => null];
        }
        if (! $u->ok() || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $verified) {
            return $fail('We could not read your email address from ' . ucfirst($provider) . '.');
        }

        return $this->loginStudent($request, strtolower($email), $profile['name'] ?: $email, $provider, $profile['id'], $profile['avatar'], (bool) $trusted);
    }

    /** Test sign-in for your own computer only (until the Google / Microsoft keys are added). */
    public function testLogin(Request $request)
    {
        abort_unless(config('services.student_test_login') && app()->environment('local'), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:190']]);

        // The test sign-in does not prove the email address, so it only counts for paid access on a developer's own computer
        return $this->loginStudent($request, strtolower($data['email']), $data['name'], 'test', null, null, app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true));
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function loginStudent(Request $request, string $email, string $name, string $provider, ?string $id, ?string $avatar, bool $trusted = false)
    {
        $student = Student::firstOrNew(['email' => $email]);
        $student->fill([
            'name' => $student->exists && $provider === 'test' ? $student->name : $name,
            'provider' => $provider, 'provider_id' => $id, 'avatar' => $avatar, 'last_login_at' => now(), 'email_trusted' => $trusted,
        ])->save();

        Auth::guard('student')->login($student, true);
        $request->session()->regenerate();

        return redirect($this->intended($request))->with('success', 'Welcome, ' . $student->name . '!');
    }

    private function intended(Request $request): string
    {
        return $request->session()->pull('student.intended') ?: route('student.portal');
    }

    /** Only allow returning to a page on this website. */
    private function safeReturn(?string $url): ?string
    {
        if (! $url) return null;
        $base = rtrim(url('/'), '/');

        return str_starts_with($url, $base . '/') || $url === $base ? $url : (str_starts_with($url, '/') && ! str_starts_with($url, '//') ? url($url) : null);
    }
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ \App\Models\SiteSetting::logoUrl() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=25">
</head>
<body class="login-page">
    <main class="login-card">
        <a href="{{ route('home') }}" class="side-brand login-brand">
            <img src="{{ \App\Models\SiteSetting::logoUrl() }}" alt="" width="40" height="40">
            <span>Etude <strong>World</strong><small>Admin</small></span>
        </a>
        <h1>Welcome back</h1>
        <p class="muted">Log in to manage your website.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="stack">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="@error('email') is-invalid @enderror">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                    <button type="button" class="show-pass" data-toggle-password="password">Show</button>
                </div>
            </div>
            <label class="check">
                <input type="checkbox" name="remember" value="1"> Keep me logged in
            </label>
            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>
    </main>
    <script src="{{ asset('js/admin.js') }}?v=2"></script>
</body>
</html>

<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/*
| Create (or reset) an admin login.
|   php artisan admin:create
| It asks for an email and password. Running it again with the same email
| changes that admin's password.
*/
Artisan::command('admin:create {email?} {--password=} {--name=Admin}', function () {
    $email = $this->argument('email') ?: text('Admin email address', required: true);

    if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
        $this->error('That is not a valid email address.');
        return 1;
    }

    $password = $this->option('password') ?: password('Choose a password (at least 8 characters)', required: true,
        validate: fn ($v) => strlen($v) < 8 ? 'Password must be at least 8 characters.' : null);

    if (strlen($password) < 8) {
        $this->error('Password must be at least 8 characters.');
        return 1;
    }

    $user = User::updateOrCreate(
        ['email' => strtolower($email)],
        ['name' => $this->option('name'), 'password' => $password],
    );

    $this->info(($user->wasRecentlyCreated ? 'Admin created: ' : 'Password updated for: ') . $user->email);
    $this->line('Log in at: ' . url('/admin/login'));
})->purpose('Create an admin login, or reset an admin password');

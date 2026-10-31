<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/** A student who signed in with Google or Microsoft. */
class Student extends Authenticatable
{
    protected $fillable = ['name', 'email', 'provider', 'provider_id', 'avatar', 'last_login_at', 'email_trusted'];

    protected $hidden = ['remember_token'];

    protected $casts = ['last_login_at' => 'datetime', 'email_trusted' => 'boolean'];

    public function results(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TestSubmission::class);
    }

    public function vocabProgress(): HasMany
    {
        return $this->hasMany(StudentVocabProgress::class);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name) ?: $this->email, 0, 1));
    }
}

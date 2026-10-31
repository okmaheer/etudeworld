<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Text the admin pastes for the AI to read (for example CELPIP Listening transcripts). */
class VocabSource extends Model
{
    protected $fillable = ['exam', 'test_id', 'module', 'text'];
}

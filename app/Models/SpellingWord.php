<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A word students must spell correctly in IELTS Listening (collected from Part 1 and Part 4 answers). */
class SpellingWord extends Model
{
    protected $fillable = ['word', 'wkey', 'category', 'alternatives', 'tests', 'shown'];

    protected $casts = ['alternatives' => 'array', 'tests' => 'array', 'shown' => 'boolean'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A Listening or Reading score (marked automatically) saved to a student's portal. */
class StudentResult extends Model
{
    protected $fillable = ['student_id', 'exam', 'test_id', 'test_name', 'module', 'mode', 'score', 'total', 'level'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    public const UPLOAD_DIR = 'uploads/achievements';

    protected $fillable = ['name', 'exam', 'score', 'quote', 'photo', 'sort_order', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function getPhotoUrlAttribute(): string
    {
        return asset($this->photo ?: 'images/student-1.svg');
    }
}

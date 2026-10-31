<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A student's "build my own course" request (Get a Quote form on /courses). */
class CourseRequest extends Model
{
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'];
    public const MODULES = ['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing', 'speaking' => 'Speaking'];

    protected $fillable = ['name', 'country', 'dial_code', 'phone', 'exam', 'classes', 'modules', 'status'];

    public function moduleLabels(): string
    {
        return collect(explode(',', (string) $this->modules))->map(fn ($m) => self::MODULES[$m] ?? ucfirst($m))->implode(', ');
    }

    public function fullPhone(): string
    {
        return $this->dial_code . ' ' . $this->phone;
    }

    /** Link that opens a WhatsApp chat with the student. */
    public function whatsappUrl(): string
    {
        $digits = preg_replace('/\D/', '', $this->dial_code) . ltrim(preg_replace('/\D/', '', $this->phone), '0');

        return 'https://wa.me/' . $digits;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The one study plan a visitor is allowed to make (found again by browser cookie or IP address). */
class StudyPlan extends Model
{
    protected $fillable = ['token', 'ip', 'plan'];

    protected $casts = ['plan' => 'array'];

    public static function forVisitor(\Illuminate\Http\Request $request): ?self
    {
        $token = (string) $request->cookie('ew_planner');

        return static::query()
            ->when($token !== '', fn ($q) => $q->where('token', $token)->orWhere('ip', $request->ip()), fn ($q) => $q->where('ip', $request->ip()))
            ->latest('id')->first();
    }
}

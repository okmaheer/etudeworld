<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Computer Based Mock Tests: add the "Access for 1 Month" label
        foreach (DB::table('course_plans')->where('slug', 'like', '%-computer-based-mock-tests')->get() as $c) {
            if (! str_contains((string) $c->chips, 'Access for 1 Month')) {
                DB::table('course_plans')->where('id', $c->id)->update(['chips' => rtrim((string) $c->chips) . "\nAccess for 1 Month"]);
            }
        }
    }

    public function down(): void
    {
    }
};

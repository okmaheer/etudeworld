<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['language_modules', 'language_lessons'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('meta_title', 160)->nullable();
                $t->string('meta_description', 320)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['language_modules', 'language_lessons'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};

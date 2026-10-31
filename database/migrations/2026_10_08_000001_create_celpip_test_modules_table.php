<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The questions of each test section (Writing, Speaking, Reading, Listening).
     * One row per test + section. The questions are stored in "content", whose
     * layout depends on the section, e.g. Writing = { task1: "...", task2: "..." }.
     */
    public function up(): void
    {
        Schema::create('celpip_test_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('celpip_test_id')->constrained('celpip_tests')->cascadeOnDelete();
            $table->string('module');                 // writing | speaking | reading | listening
            $table->json('content')->nullable();
            $table->timestamps();
            $table->unique(['celpip_test_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celpip_test_modules');
    }
};

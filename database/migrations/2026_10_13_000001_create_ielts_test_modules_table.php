<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The questions of each IELTS test section (Speaking for now; Listening, Reading and Writing later).
     * One row per test + section; the questions are stored in "content".
     */
    public function up(): void
    {
        Schema::create('ielts_test_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_test_id')->constrained('ielts_tests')->cascadeOnDelete();
            $table->string('module');                 // listening | reading | writing | speaking
            $table->json('content')->nullable();
            $table->timestamps();
            $table->unique(['ielts_test_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ielts_test_modules');
    }
};

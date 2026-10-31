<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toefl_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();            // used by the full mock test address
            $table->string('type')->default('free');     // free | paid
            $table->string('status')->default('draft');  // active | draft
            $table->timestamps();
        });

        // One row per test + section (reading | listening | writing | speaking).
        // Each section has its own address and meta tags; the questions live in "content".
        Schema::create('toefl_test_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toefl_test_id')->constrained('toefl_tests')->cascadeOnDelete();
            $table->string('module');
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->json('content')->nullable();
            $table->timestamps();
            $table->unique(['toefl_test_id', 'module']);
            $table->unique(['module', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toefl_test_modules');
        Schema::dropIfExists('toefl_tests');
    }
};

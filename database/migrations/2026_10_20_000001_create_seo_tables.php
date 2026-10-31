<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin → SEO Pages: the title, description, heading and text of the exam list pages
        // (full mock tests, Reading, Listening, Writing, Speaking). One row per page.
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('exam_group');            // e.g. ielts-academic, celpip-ls, toefl
            $table->string('page');                  // landing | hub | mock | reading | listening | writing | speaking
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('h1')->nullable();
            $table->text('intro')->nullable();
            $table->longText('content')->nullable(); // extra text (with H2 headings) shown under the tests
            $table->timestamps();
            $table->unique(['exam_group', 'page']);
        });

        // Search-result settings of one test's own pages: the full mock test and each section.
        Schema::create('test_seo', function (Blueprint $table) {
            $table->id();
            $table->string('exam');                  // ielts | celpip | toefl
            $table->unsignedBigInteger('test_id');
            $table->string('page');                  // mock | reading | listening | writing | speaking
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('indexable')->default(false); // may Google list this page?
            $table->timestamps();
            $table->unique(['exam', 'test_id', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_seo');
        Schema::dropIfExists('seo_pages');
    }
};

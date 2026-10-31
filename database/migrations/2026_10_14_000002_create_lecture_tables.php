<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paid lectures: courses made of lessons (videos), student purchases, and a question thread under each lesson.
     */
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('CAD');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url')->nullable();       // link from the video host
            $table->string('notes_path')->nullable();      // optional PDF notes
            $table->boolean('is_preview')->default(false); // free to watch without buying
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('course_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('payment_ref')->nullable();     // filled in once the payment gateway exists
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'course_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seconds_watched')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->unique(['student_id', 'lesson_id']);
        });

        Schema::create('lesson_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('lesson_comments')->cascadeOnDelete();
            $table->boolean('from_teacher')->default(false);
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_comments');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('course_purchases');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('courses');
    }
};

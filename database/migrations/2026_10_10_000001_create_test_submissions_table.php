<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Students' Writing and Speaking answers, waiting for the teacher to evaluate them
     * (Admin → CELPIP Evaluation / IELTS Evaluation). Listening and Reading are marked automatically
     * and are not stored here.
     */
    public function up(): void
    {
        Schema::create('test_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('exam', 20)->default('celpip');        // celpip | ielts
            $table->unsignedBigInteger('test_id')->nullable();    // the test the student took
            $table->string('test_name');
            $table->string('mode', 20)->default('practice');      // practice | mock
            $table->string('module', 20);                          // writing | speaking
            $table->string('attempt', 40)->nullable();             // groups the sections of one mock test
            $table->string('student_name');
            $table->string('student_email');
            $table->json('answers');                               // the questions + the student's answers / recordings
            $table->string('status', 20)->default('pending');     // pending | evaluated
            $table->string('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->index(['exam', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_submissions');
    }
};

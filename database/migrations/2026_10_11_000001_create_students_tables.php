<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Students (sign in with Google or Microsoft) and their test results.
     *   students         – one row per student (their email is also used later to confirm payments)
     *   student_results  – Listening / Reading scores (marked automatically) shown in the student's portal
     *   test_submissions – gets student_id, so Writing / Speaking evaluations appear in the portal too
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('provider', 20)->nullable();       // google | microsoft | test
            $table->string('provider_id')->nullable();
            $table->string('avatar', 500)->nullable();
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('exam', 20);                        // celpip | ielts | tef | hsk | goethe
            $table->unsignedBigInteger('test_id')->nullable();
            $table->string('test_name');
            $table->string('module', 20);                      // listening | reading
            $table->string('mode', 20)->default('practice');
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total');
            $table->string('level')->nullable();               // e.g. "CELPIP level 8 · CLB 8"
            $table->timestamps();
        });

        Schema::table('test_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('test_submissions', fn (Blueprint $table) => $table->dropColumn('student_id'));
        Schema::dropIfExists('student_results');
        Schema::dropIfExists('students');
    }
};

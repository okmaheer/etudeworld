<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Build your own course" requests sent from the bottom of /courses (Admin → Courses → Course Requests)
        if (! Schema::hasTable('course_requests')) {
            Schema::create('course_requests', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('country', 80);
                $table->string('dial_code', 8);
                $table->string('phone', 30);
                $table->string('exam', 10);                      // ielts | celpip | toefl
                $table->unsignedSmallInteger('classes');
                $table->string('modules', 120);                  // e.g. "listening,writing"
                $table->string('status', 12)->default('new');    // new | contacted | closed
                $table->timestamps();
                $table->index(['status', 'created_at']);
            });
        }

        // Students who bought a course and whose payment the payment gateway confirmed (Admin → Courses → Course Bookings)
        if (! Schema::hasTable('course_bookings')) {
            Schema::create('course_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_plan_id')->nullable();
                $table->string('course_title');                  // saved as it was when bought
                $table->string('exam', 10)->nullable();
                $table->unsignedBigInteger('student_id')->nullable();
                $table->string('student_name', 120);
                $table->string('student_email')->index();
                $table->string('student_phone', 40)->nullable();
                $table->decimal('amount', 8, 2);
                $table->string('currency', 5)->default('USD');
                $table->string('gateway', 30)->nullable();       // e.g. stripe, paypal
                $table->string('payment_reference', 120)->nullable()->unique();
                $table->timestamp('paid_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_bookings');
        Schema::dropIfExists('course_requests');
    }
};

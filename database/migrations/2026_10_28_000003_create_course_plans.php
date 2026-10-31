<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_plans')) {
            \App\Models\Course::seedDefaults();
            return;
        }

        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->string('exam', 10);                       // ielts | celpip | toefl
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 400)->nullable();       // one or two lines under the title
            $table->string('badge', 40)->nullable();          // small ribbon, e.g. "Best value"
            $table->text('chips')->nullable();                // short facts, one per line: "7 Classes", "1 hour each class"
            $table->text('features')->nullable();             // ticked list, one per line
            $table->string('note', 200)->nullable();          // small grey line, e.g. "Expert feedback not available"
            $table->decimal('price', 8, 2)->default(0);
            $table->string('currency', 5)->default('USD');
            $table->string('button_label', 40)->default('Purchase Now');
            $table->string('button_url', 300)->nullable();    // empty = the payments page
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['exam', 'is_published', 'sort_order']);
        });

        \App\Models\Course::seedDefaults();     // the standard IELTS / CELPIP / TOEFL courses
    }

    public function down(): void
    {
        Schema::dropIfExists('course_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_vocab_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ielts_test_id')->constrained('ielts_tests')->cascadeOnDelete();
            $t->string('module', 20);
            $t->json('known')->nullable();   // ids of the words the student marked as learned
            $t->timestamps();
            $t->unique(['student_id', 'ielts_test_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_vocab_progress');
    }
};

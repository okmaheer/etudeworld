<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('level_tests', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('instructions')->nullable();
            $t->string('meta_title')->nullable();
            $t->text('meta_description')->nullable();
            $t->json('thresholds')->nullable();      // optional manual level cut-offs
            $t->string('status')->default('draft');  // active | draft
            $t->timestamps();
        });

        Schema::create('level_test_sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('level_test_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('number');
            $t->text('instructions')->nullable();
            $t->json('passages')->nullable();        // Section 3: two passages [{title, body}]
            $t->timestamps();
            $t->unique(['level_test_id', 'number']);
        });

        Schema::create('level_test_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('level_test_section_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('passage')->nullable(); // 1 or 2 (Section 3), null = not tied to a passage
            $t->unsignedInteger('position')->default(0);
            $t->string('type');                      // mcq | fitb
            $t->text('prompt');
            $t->json('options')->nullable();         // mcq: four options
            $t->string('answer');                    // mcq: option number 0-3; fitb: accepted answers split by |
            $t->string('level')->nullable();         // A1..C2 (admin note)
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_test_questions');
        Schema::dropIfExists('level_test_sections');
        Schema::dropIfExists('level_tests');
    }
};

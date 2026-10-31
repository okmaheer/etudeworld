<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vocabulary Builder: the difficult words of each IELTS test section (Listening / Reading),
     * with meanings and translations, plus weekly sets that group them for students.
     */
    public function up(): void
    {
        Schema::create('vocab_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ielts_test_id')->nullable()->constrained('ielts_tests')->nullOnDelete();
            $table->string('module')->nullable();          // listening | reading
            $table->string('word');                        // word, collocation or phrase
            $table->string('type')->nullable();            // noun, verb, phrase ...
            $table->text('meaning')->nullable();           // simple English meaning
            $table->json('synonyms')->nullable();          // ["...", "..."]
            $table->text('example')->nullable();           // example sentence
            $table->json('translations')->nullable();      // {"hi": "...", "ur": "..."}
            $table->boolean('approved')->default(false);   // only approved words are shown to students
            $table->string('source')->default('manual');   // manual | auto
            $table->timestamps();
            $table->index(['ielts_test_id', 'module']);
        });

        Schema::create('vocab_sets', function (Blueprint $table) {
            $table->id();
            $table->string('title');                       // e.g. "Week 1 – Environment"
            $table->unsignedInteger('position')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
        });

        Schema::create('vocab_set_word', function (Blueprint $table) {
            $table->foreignId('vocab_set_id')->constrained('vocab_sets')->cascadeOnDelete();
            $table->foreignId('vocab_word_id')->constrained('vocab_words')->cascadeOnDelete();
            $table->primary(['vocab_set_id', 'vocab_word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_set_word');
        Schema::dropIfExists('vocab_sets');
        Schema::dropIfExists('vocab_words');
    }
};

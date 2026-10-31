<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Important spellings collected from the answers of IELTS Listening Part 1 and Part 4. */
    public function up(): void
    {
        Schema::create('spelling_words', function (Blueprint $t) {
            $t->id();
            $t->string('word', 120);
            $t->string('wkey', 120)->unique();           // lower-case, used to merge duplicates
            $t->string('category', 60)->default('General words');
            $t->json('alternatives')->nullable();        // other accepted spellings, e.g. "color"
            $t->json('tests')->nullable();               // ids of the IELTS tests where the word appears
            $t->boolean('shown')->default(false);        // only ticked words are shown to students
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spelling_words');
    }
};

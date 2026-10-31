<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_modules', function (Blueprint $table) {
            $table->id();
            $table->string('language', 30)->index();          // e.g. "german"
            $table->string('title');
            $table->string('slug');
            $table->string('icon', 16)->nullable();            // an emoji
            $table->string('description', 400)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['language', 'slug']);
        });

        Schema::create('language_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_module_id')->constrained('language_modules')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('level', 4)->nullable();            // A1, A2, B1, B2, C1
            $table->string('summary', 400)->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->unique(['language_module_id', 'slug']);
        });

        // A starting set of German modules. Rename, reorder or delete them in Admin.
        $now = now();
        $rows = [
            ['Alphabet & Pronunciation', 'alphabet-and-pronunciation', '🔤', 'Letters, sounds and the tricky umlauts (ä, ö, ü) and ß.'],
            ['Vocabulary', 'vocabulary', '📚', 'Everyday words grouped by topic, with articles (der, die, das).'],
            ['Grammar', 'grammar', '🧩', 'Cases, verb endings and word order, explained step by step.'],
            ['Everyday Phrases', 'everyday-phrases', '💬', 'Greetings, shopping, travel and other phrases you can use right away.'],
            ['Listening', 'listening', '🎧', 'Train your ear with short German audio and simple questions.'],
            ['Reading', 'reading', '📖', 'Short German texts at your level, with help for new words.'],
        ];
        foreach ($rows as $i => [$title, $slug, $icon, $desc]) {
            DB::table('language_modules')->insert([
                'language' => 'german', 'title' => $title, 'slug' => $slug, 'icon' => $icon,
                'description' => $desc, 'sort_order' => $i + 1, 'is_published' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('language_lessons');
        Schema::dropIfExists('language_modules');
    }
};

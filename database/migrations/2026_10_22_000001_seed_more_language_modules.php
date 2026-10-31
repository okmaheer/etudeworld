<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Starting modules for English, French and Chinese (German was added earlier). Rename, reorder or delete them in Admin. */
    public function up(): void
    {
        $now = now();
        $sets = [
            'english' => [
                ['Grammar', 'grammar', '🧩', 'Tenses, articles, prepositions and sentence structure, explained simply.'],
                ['Vocabulary', 'vocabulary', '📚', 'Useful everyday and academic words, grouped by topic.'],
                ['Everyday Phrases', 'everyday-phrases', '💬', 'Phrases for work, shopping, travel and daily life.'],
                ['Pronunciation', 'pronunciation', '🔤', 'Sounds, stress and intonation to help you be understood.'],
                ['Listening', 'listening', '🎧', 'Train your ear with short audio and simple questions.'],
                ['Reading', 'reading', '📖', 'Short texts at your level, with help for new words.'],
            ],
            'french' => [
                ['Alphabet & Pronunciation', 'alphabet-and-pronunciation', '🔤', 'Letters, accents and the sounds of French.'],
                ['Vocabulary', 'vocabulary', '📚', 'Everyday words grouped by topic, with le, la and les.'],
                ['Grammar', 'grammar', '🧩', 'Verbs, gender and sentence structure, explained step by step.'],
                ['Everyday Phrases', 'everyday-phrases', '💬', 'Greetings, shopping, travel and other phrases you can use right away.'],
                ['Listening', 'listening', '🎧', 'Train your ear with short French audio and simple questions.'],
                ['Reading', 'reading', '📖', 'Short French texts at your level, with help for new words.'],
            ],
            'chinese' => [
                ['Pinyin & Pronunciation', 'pinyin-and-pronunciation', '🔤', 'The sounds and four tones of Mandarin, written in pinyin.'],
                ['Characters', 'characters', '✍️', 'Learn to read and write common Chinese characters.'],
                ['Vocabulary', 'vocabulary', '📚', 'Everyday words grouped by topic.'],
                ['Grammar', 'grammar', '🧩', 'Sentence patterns and measure words, explained simply.'],
                ['Everyday Phrases', 'everyday-phrases', '💬', 'Greetings, shopping, travel and other phrases you can use right away.'],
                ['Listening', 'listening', '🎧', 'Train your ear with short Mandarin audio and simple questions.'],
            ],
        ];
        foreach ($sets as $language => $rows) {
            if (DB::table('language_modules')->where('language', $language)->exists()) continue;
            foreach ($rows as $i => [$title, $slug, $icon, $desc]) {
                DB::table('language_modules')->insert([
                    'language' => $language, 'title' => $title, 'slug' => $slug, 'icon' => $icon, 'description' => $desc,
                    'sort_order' => $i + 1, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('language_modules')->whereIn('language', ['english', 'french', 'chinese'])->delete();
    }
};

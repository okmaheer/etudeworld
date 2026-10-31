<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** Vocabulary for CELPIP tests, topic lists for Writing / Speaking, and progress for every kind of list. */
    public function up(): void
    {
        Schema::table('vocab_words', function (Blueprint $t) {
            $t->string('exam', 10)->default('ielts')->after('id');
            $t->unsignedBigInteger('celpip_test_id')->nullable()->index();
            $t->unsignedBigInteger('topic_id')->nullable()->index();
        });

        Schema::create('vocab_topics', function (Blueprint $t) {
            $t->id();
            $t->string('exam', 10);                 // ielts | celpip
            $t->string('skill', 20);                // writing | speaking
            $t->string('name');
            $t->string('slug');
            $t->string('status', 10)->default('active');
            $t->timestamps();
            $t->unique(['exam', 'skill', 'slug']);
        });

        // Transcripts pasted by the admin (CELPIP Listening has no transcript box of its own)
        Schema::create('vocab_sources', function (Blueprint $t) {
            $t->id();
            $t->string('exam', 10);
            $t->unsignedBigInteger('test_id');
            $t->string('module', 20);
            $t->longText('text')->nullable();
            $t->timestamps();
            $t->unique(['exam', 'test_id', 'module']);
        });

        Schema::table('student_vocab_progress', function (Blueprint $t) {
            $t->unsignedBigInteger('ielts_test_id')->nullable()->change();
            $t->string('list_key', 60)->nullable()->index();
        });
        foreach (DB::table('student_vocab_progress')->get() as $p) {
            DB::table('student_vocab_progress')->where('id', $p->id)->update(['list_key' => "ielts:{$p->module}:{$p->ielts_test_id}"]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_sources');
        Schema::dropIfExists('vocab_topics');
    }
};

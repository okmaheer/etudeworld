<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sample answers: the topic written by the admin (Transport, Health …), used by the topic filter on the Sample Answers pages
        if (! Schema::hasColumn('blogs', 'topic')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->string('topic', 80)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('blogs', 'topic')) {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropIndex(['topic']);
                $table->dropColumn('topic');
            });
        }
    }
};

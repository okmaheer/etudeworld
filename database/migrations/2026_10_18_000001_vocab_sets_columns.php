<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Weekly word sets: remember which list a set was cut from and its week number. */
    public function up(): void
    {
        Schema::table('vocab_sets', function (Blueprint $table) {
            $table->string('list_key')->nullable()->index();
            $table->string('group_label')->nullable();
            $table->unsignedInteger('week')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('vocab_sets', function (Blueprint $table) {
            $table->dropColumn(['list_key', 'group_label', 'week']);
        });
    }
};

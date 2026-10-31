<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** IELTS practice tests (Admin → Manage IELTS). Works like celpip_tests. */
    public function up(): void
    {
        Schema::create('ielts_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();            // the "URL" part
            $table->string('format');                    // academic | gt
            $table->string('type');                      // free | paid
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('status')->default('draft');  // active | draft
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ielts_tests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();             // the "URL" part, e.g. how-to-score-band-8
            $table->string('category');                   // ielts | celpip | study-methods | language-learning
            $table->longText('content')->nullable();      // the formatted blog text (HTML)
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('image')->nullable();          // path inside /public, e.g. uploads/blogs/abc.jpg
            $table->string('image_alt')->nullable();
            $table->string('status')->default('draft');   // published | draft
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();      // used in the author page address: /author/{slug}
            $table->text('bio')->nullable();        // formatted text (HTML)
            $table->string('image')->nullable();    // path inside /public, e.g. uploads/authors/abc.jpg
            $table->string('facebook')->nullable();
            $table->string('x')->nullable();
            $table->string('linkedin')->nullable();
            $table->timestamps();
        });

        // Link every blog to (optionally) one author.
        Schema::table('blogs', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->after('category')
                  ->constrained('authors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
        });
        Schema::dropIfExists('authors');
    }
};

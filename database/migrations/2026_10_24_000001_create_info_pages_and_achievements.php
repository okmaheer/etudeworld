<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // About, Contact, Privacy, Terms … written in Admin → Info Pages (a page shows only when it is published AND has text)
        Schema::create('info_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('is_system')->default(false);   // the four built-in pages: their address cannot change or be deleted
            $table->longText('body')->nullable();
            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Student success stories shown on the home page (Admin → Achievements)
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('exam')->nullable();
            $table->string('score', 60)->nullable();
            $table->text('quote')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach ([['About Us', 'about-us'], ['Contact Us', 'contact-us'], ['Privacy Policy', 'privacy-policy'], ['Terms and Agreements', 'terms-and-agreements']] as [$title, $slug]) {
            DB::table('info_pages')->insert(['title' => $title, 'slug' => $slug, 'is_system' => true, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        // The sample entries the home page showed before. Replace them with real students (with their permission).
        $samples = [
            ['Sample Student A', 'IELTS Academic', 'Band 8.0', 'images/student-1.svg', 'The timed mock tests and writing feedback helped me jump from 6.5 to 8.0 in two months.'],
            ['Sample Student B', 'CELPIP General', 'CLB 10', 'images/student-2.svg', 'Practising every CELPIP task type made the real test feel easy. Exactly what I needed for Express Entry.'],
            ['Sample Student C', 'IELTS General', 'Band 7.5', 'images/student-3.svg', 'Spaced repetition for vocabulary changed everything. My reading score went up a full band.'],
            ['Sample Student D', 'CELPIP LS', 'CLB 9', 'images/student-4.svg', 'Shadowing practice made listening so much easier. I passed on my first attempt.'],
        ];
        foreach ($samples as $i => [$name, $exam, $score, $photo, $quote]) {
            DB::table('achievements')->insert(['name' => $name, 'exam' => $exam, 'score' => $score, 'photo' => $photo, 'quote' => $quote, 'sort_order' => $i + 1, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('info_pages');
    }
};

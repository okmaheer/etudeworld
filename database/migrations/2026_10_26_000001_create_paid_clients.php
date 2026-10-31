<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin → Manage Paid Clients: one row = one paid email for one exam, between two dates
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();           // always saved in lower case
                $table->string('name');
                $table->string('phone', 40)->nullable();
                $table->string('exam', 10);                 // ielts | celpip | toefl
                $table->unsignedTinyInteger('days');        // 1 – 30
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');                // access lasts until this moment (end of the last day)
                $table->timestamps();
                $table->index(['email', 'exam', 'ends_at']);
            });
        }

        // Paid access is only given when the student's email address was confirmed by Google / Microsoft
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('email_trusted')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('email_trusted');
        });
        Schema::dropIfExists('clients');
    }
};

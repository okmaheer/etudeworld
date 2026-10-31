<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $t) {
            $t->id();
            $t->string('token', 64)->unique();   // kept in the visitor's browser (cookie)
            $t->string('ip', 45)->index();       // the visitor's address
            $t->json('plan');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plans');
    }
};

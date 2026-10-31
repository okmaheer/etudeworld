<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Messages sent from the Contact Us form (Admin → Messages)
        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('email', 190);
                $table->string('phone', 40)->nullable();
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
                $table->index(['is_read', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};

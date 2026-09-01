<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizer_items', function (Blueprint $table) {
            $table->id();
            $table->string('section', 32)->index();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('source_key', 80)->nullable()->unique();
            $table->timestamps();

            $table->index(['section', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_items');
    }
};

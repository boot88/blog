<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->string('title');                 // Название будильника
            $table->text('note')->nullable();        // Что сделать (описание)
            $table->date('date')->nullable();        // Если null — ежедневный (по времени)
            $table->time('time');                    // Время срабатывания
            $table->boolean('enabled')->default(true);
            $table->string('timezone')->default(config('app.timezone')); // на будущее
            $table->timestamp('last_triggered_at')->nullable(); // чтобы не срабатывал бесконечно
            $table->timestamps();

            $table->index(['enabled', 'date', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarms');
    }
};

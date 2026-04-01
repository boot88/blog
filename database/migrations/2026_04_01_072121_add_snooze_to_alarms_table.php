<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('alarms', function (Blueprint $table) {
        if (!Schema::hasColumn('alarms', 'duration')) {
            $table->integer('duration')->default(10);
        }
        if (!Schema::hasColumn('alarms', 'snooze_duration')) {
            $table->integer('snooze_duration')->default(10);
        }
        if (!Schema::hasColumn('alarms', 'snooze_repeats')) {
            $table->integer('snooze_repeats')->default(3);
        }
    });
}

    /**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::table('alarms', function (Blueprint $table) {
        // Обязательно перечисляем все добавленные поля для отката
        $table->dropColumn(['duration', 'snooze_duration', 'snooze_repeats']);
    });
}
};











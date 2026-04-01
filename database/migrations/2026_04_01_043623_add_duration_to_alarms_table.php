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
            // Добавляем колонку duration
            $table->integer('duration')->default(10)->after('id'); // after опционально, чтобы поставить колонку в нужное место
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarms', function (Blueprint $table) {
            // Удаляем колонку при откате миграции
            $table->dropColumn('duration');
        });
    }
};

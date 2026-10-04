<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('wage_activity_id')->nullable()->constrained('wage_activities')->nullOnDelete();
            // Snapshot nama & nominal saat dicatat — riwayat upah tak berubah bila
            // katalog wage_activities diedit/dihapus belakangan (06 P8).
            $table->string('name', 100);
            $table->unsignedBigInteger('bonus_amount');
            $table->timestamp('created_at')->nullable();

            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_activities');
    }
};

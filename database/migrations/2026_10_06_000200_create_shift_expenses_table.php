<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->string('description', 255);
            $table->unsignedBigInteger('amount');
            $table->timestamp('created_at')->nullable();

            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_expenses');
    }
};

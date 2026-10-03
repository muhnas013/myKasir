<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->enum('unit', ['g', 'ml', 'pcs']);
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('min_qty', 12, 3)->default(0);
            $table->unsignedBigInteger('avg_cost')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_option_id')->nullable()->constrained('variant_options')->cascadeOnDelete();
            $table->decimal('qty', 12, 3);
            $table->timestamps();

            $table->unique(['ingredient_id', 'product_id', 'variant_option_id']);
        });

        // Lengkapi FK yang sengaja ditunda sejak migrasi F3 (stock_movements.ingredient_id).
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
        });

        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
    }
};

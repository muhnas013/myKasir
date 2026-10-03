<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('sku', 20)->unique();
            $table->string('name', 100);
            $table->unsignedBigInteger('price');
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(false);
            $table->integer('stock_qty')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('variant_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 50);
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('max_select')->default(1);
            $table->timestamps();
        });

        Schema::create('variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_group_id')->constrained('variant_groups')->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedBigInteger('price_delta')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_options');
        Schema::dropIfExists('variant_groups');
        Schema::dropIfExists('products');
    }
};

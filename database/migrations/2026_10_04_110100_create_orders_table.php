<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 12);
            $table->date('business_date');
            $table->foreignId('shift_id')->constrained('shifts');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['open', 'paid', 'void'])->index();
            $table->enum('order_type', ['dine_in', 'take_away']);
            $table->string('customer_label', 50)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('service')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->bigInteger('rounding')->default(0);
            $table->unsignedBigInteger('total');
            $table->foreignId('discount_approved_by')->nullable()->constrained('users');
            $table->char('idempotency_key', 36)->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['business_date', 'number']);
            $table->index(['business_date', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 100);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedSmallInteger('qty');
            $table->unsignedBigInteger('line_total');
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->json('options')->nullable();
            $table->string('note', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('method', ['cash', 'qris', 'card']);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('paid_amount');
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->string('reference', 50)->nullable();
            $table->timestamps();
        });

        // ingredient_id tanpa FK: tabel ingredients baru ada di F4 (FK ditambah di sana).
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ingredient_id')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->enum('type', ['sale', 'void_return', 'purchase', 'adjustment']);
            $table->decimal('qty', 12, 3);
            $table->unsignedBigInteger('total_cost')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('user_id')->constrained('users');
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['ingredient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};

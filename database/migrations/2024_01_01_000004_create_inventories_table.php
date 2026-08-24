<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->timestamps();
            
            // Composite unique constraint: one inventory record per product per branch
            $table->unique(['product_id', 'branch_id']);
            $table->index('branch_id');
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

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
    Schema::create('reconciled_data', function (Blueprint $table) {
        $table->id();
        $table->foreignId('batch_id')->constrained('etl_batches')->onDelete('cascade');
        
        // Data produk yang sudah dinormalisasi (cleaned)
        $table->string('product_name');
        $table->integer('total_quantity')->default(0);
        $table->decimal('total_revenue', 15, 2)->default(0);
        
        // Referensi ke sumber data
        $table->string('shopee_order_id')->nullable();
        $table->string('tiktok_live_id')->nullable();
        
        // Breakdown (opsional - untuk transparansi)
        $table->integer('shopee_quantity')->nullable();
        $table->integer('tiktok_quantity')->nullable();
        $table->decimal('shopee_revenue', 15, 2)->nullable();
        $table->decimal('tiktok_revenue', 15, 2)->nullable();
        
        $table->timestamps();
        
        // Index untuk performa query
        $table->index('batch_id');
        $table->index('product_name');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciled_data');
    }
};

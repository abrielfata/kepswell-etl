<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('raw_shopee', function (Blueprint $table) {
        $table->id();
        $table->foreignId('batch_id')->constrained('etl_batches')->onDelete('cascade');
        
        // Kolom-kolom sesuai format export Shopee (sesuaikan dengan file asli Aujin)
        $table->string('order_id')->nullable();
        $table->string('product_name')->nullable();
        $table->integer('quantity')->nullable();
        $table->decimal('price', 15, 2)->nullable();
        $table->decimal('total', 15, 2)->nullable();
        $table->timestamp('order_date')->nullable();
        
        // Simpan semua data mentah sebagai JSON (untuk fleksibilitas)
        $table->json('raw_data')->nullable();
        
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_shopee');
    }
};

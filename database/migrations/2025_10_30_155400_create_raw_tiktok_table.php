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
    Schema::create('raw_tiktok', function (Blueprint $table) {
        $table->id();
        $table->foreignId('batch_id')->constrained('etl_batches')->onDelete('cascade');
        
        // Kolom-kolom sesuai format export TikTok (sesuaikan dengan file asli Aujin)
        $table->string('live_id')->nullable();
        $table->string('host_name')->nullable();
        $table->string('product_name')->nullable();
        $table->integer('product_sold')->nullable();
        $table->decimal('revenue', 15, 2)->nullable();
        $table->timestamp('live_date')->nullable();
        
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
        Schema::dropIfExists('raw_tiktok');
    }
};

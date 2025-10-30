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
    Schema::create('etl_batches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('batch_name');
        $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
        $table->string('shopee_file')->nullable();
        $table->string('tiktok_file')->nullable();
        $table->text('error_message')->nullable(); // Untuk simpan pesan error jika gagal
        $table->timestamp('processed_at')->nullable(); // Kapan proses ETL selesai
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etl_batches');
    }
};

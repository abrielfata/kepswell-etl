<?php

namespace App\Jobs;

use App\Models\EtlBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessEtlBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 menit max
    public $tries = 3; // Retry 3x jika gagal

    protected $batchId;

    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        try {
            $batch = EtlBatch::findOrFail($this->batchId);
            
            // Update status jadi 'processing'
            $batch->update(['status' => 'processing']);
            
            Log::info("ETL Started for Batch #{$this->batchId}");
            
            // TODO: Step 1 - Extract & Parse Shopee file
            // TODO: Step 2 - Extract & Parse TikTok file
            // TODO: Step 3 - Reconcile (gabungkan data)
            
            // Update status jadi 'completed'
            $batch->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
            
            Log::info("ETL Completed for Batch #{$this->batchId}");
            
        } catch (Exception $e) {
            Log::error("ETL Failed for Batch #{$this->batchId}: " . $e->getMessage());
            
            // Update status jadi 'failed'
            EtlBatch::where('id', $this->batchId)->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw agar masuk failed_jobs table
        }
    }
}
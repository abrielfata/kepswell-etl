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
            
            // Step 1: Extract & Parse Shopee file
            if ($batch->shopee_file) {
                $this->processShopeeFile($batch);
            }
            
            // Step 2: Extract & Parse TikTok file
            if ($batch->tiktok_file) {
                $this->processTiktokFile($batch);
            }
            
            // TODO: Step 3 - Reconcile (gabungkan data)
            $reconciliationService = new \App\Services\ReconciliationService();
            $reconciliationService->reconcile($batch);
            
            // Update status jadi 'completed'
            $batch->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
            
            Log::info("ETL Completed for Batch #{$this->batchId}");
            
        } catch (Exception $e) {
            Log::error("ETL Failed for Batch #{$this->batchId}: " . $e->getMessage());
    
            EtlBatch::where('id', $this->batchId)->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Process Shopee file
     */
    private function processShopeeFile(EtlBatch $batch): void
    {
        $parser = new \App\Services\ShopeeParserService();
        $filePath = storage_path('app/uploads/' . $batch->shopee_file);
        
        $records = $parser->parse($filePath);
        
        // Bulk insert ke raw_shopee
        foreach ($records as $record) {
            $batch->rawShopee()->create($record);
        }
        
        Log::info("Shopee: Inserted " . count($records) . " records");
    }

    /**
     * Process TikTok file
     */
    private function processTiktokFile(EtlBatch $batch): void
    {
        $parser = new \App\Services\TiktokParserService();
        $filePath = storage_path('app/uploads/' . $batch->tiktok_file);
        
        $records = $parser->parse($filePath);
        
        // Bulk insert ke raw_tiktok
        foreach ($records as $record) {
            $batch->rawTiktok()->create($record);
        }
        
        Log::info("TikTok: Inserted " . count($records) . " records");
    }
}
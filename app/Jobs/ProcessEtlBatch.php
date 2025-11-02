<?php

namespace App\Jobs;

use App\Models\EtlBatch;
use App\Models\RawShopee;
use App\Models\RawTiktok;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            
            // Gunakan transaction untuk ensure data integrity
            DB::transaction(function () use ($batch) {
                // Step 1: Extract & Parse Shopee file
                if ($batch->shopee_file) {
                    $this->processShopeeFile($batch);
                }
                
                // Step 2: Extract & Parse TikTok file
                if ($batch->tiktok_file) {
                    $this->processTiktokFile($batch);
                }
                
                // Step 3: Reconcile (gabungkan data)
                $reconciliationService = new \App\Services\ReconciliationService();
                $reconciliationService->reconcile($batch);
            });
            
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
        
        if (empty($records)) {
            Log::warning("Shopee: No records to insert");
            return;
        }
        
        // Prepare data untuk bulk insert
        $insertData = [];
        $now = now();
        
        foreach ($records as $record) {
            $insertData[] = [
                'batch_id' => $batch->id,
                'order_id' => $record['order_id'],
                'product_name' => $record['product_name'],
                'quantity' => $record['quantity'],
                'price' => $record['price'],
                'total' => $record['total'],
                'order_date' => $record['order_date'],
                'raw_data' => json_encode($record['raw_data']), // JSON encode untuk raw_data
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Bulk insert dengan chunk untuk menghindari memory issue
        $chunks = array_chunk($insertData, 500);
        $totalInserted = 0;
        
        foreach ($chunks as $chunk) {
            RawShopee::insert($chunk);
            $totalInserted += count($chunk);
        }
        
        Log::info("Shopee: Bulk inserted {$totalInserted} records");
    }

    /**
     * Process TikTok file
     */
    private function processTiktokFile(EtlBatch $batch): void
    {
        $parser = new \App\Services\TiktokParserService();
        $filePath = storage_path('app/uploads/' . $batch->tiktok_file);
        
        $records = $parser->parse($filePath);
        
        if (empty($records)) {
            Log::warning("TikTok: No records to insert");
            return;
        }
        
        // Prepare data untuk bulk insert
        $insertData = [];
        $now = now();
        
        foreach ($records as $record) {
            $insertData[] = [
                'batch_id' => $batch->id,
                'live_id' => $record['live_id'],
                'host_name' => $record['host_name'],
                'product_name' => $record['product_name'],
                'product_sold' => $record['product_sold'],
                'revenue' => $record['revenue'],
                'live_date' => $record['live_date'],
                'raw_data' => json_encode($record['raw_data']), // JSON encode untuk raw_data
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        // Bulk insert dengan chunk untuk menghindari memory issue
        $chunks = array_chunk($insertData, 500);
        $totalInserted = 0;
        
        foreach ($chunks as $chunk) {
            RawTiktok::insert($chunk);
            $totalInserted += count($chunk);
        }
        
        Log::info("TikTok: Bulk inserted {$totalInserted} records");
    }
}
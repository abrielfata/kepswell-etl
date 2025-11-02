<?php

namespace App\Services;

use App\Models\EtlBatch;
use App\Models\ReconciledData;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    /**
     * Reconcile (gabungkan) data Shopee + TikTok berdasarkan product_name
     */
    public function reconcile(EtlBatch $batch): void
    {
        // Ambil semua data mentah
        $shopeeRecords = $batch->rawShopee;
        $tiktokRecords = $batch->rawTiktok;
        
        // Group by product_name
        $shopeeByProduct = $shopeeRecords->groupBy('product_name');
        $tiktokByProduct = $tiktokRecords->groupBy('product_name');
        
        // Ambil semua unique product names dari kedua sumber
        $allProductNames = $shopeeByProduct->keys()
            ->merge($tiktokByProduct->keys())
            ->unique()
            ->filter(function($productName) {
                // Filter out empty product names
                return !empty(trim($productName));
            });
        
        Log::info("Reconciliation: Found " . $allProductNames->count() . " unique products");
        
        if ($allProductNames->isEmpty()) {
            Log::warning("Reconciliation: No valid product names found. Skipping reconciliation.");
            return;
        }
        
        // Prepare data untuk bulk insert
        $reconciledData = [];
        $now = now();
        $skippedEmpty = 0;
        
        foreach ($allProductNames as $productName) {
            // Skip jika product_name masih kosong setelah trim
            if (empty(trim($productName))) {
                $skippedEmpty++;
                continue;
            }
            
            $shopeeData = $shopeeByProduct->get($productName);
            $tiktokData = $tiktokByProduct->get($productName);
            
            // Hitung total dari Shopee
            $shopeeQty = $shopeeData ? $shopeeData->sum('quantity') : 0;
            $shopeeRevenue = $shopeeData ? $shopeeData->sum('total') : 0;
            $shopeeOrderIds = $shopeeData ? $shopeeData->pluck('order_id')->filter()->implode(',') : null;
            
            // Hitung total dari TikTok
            $tiktokQty = $tiktokData ? $tiktokData->sum('product_sold') : 0;
            $tiktokRevenue = $tiktokData ? $tiktokData->sum('revenue') : 0;
            $tiktokLiveIds = $tiktokData ? $tiktokData->pluck('live_id')->unique()->filter()->implode(',') : null;
            
            // Prepare data untuk bulk insert
            $reconciledData[] = [
                'batch_id' => $batch->id,
                'product_name' => $productName,
                'total_quantity' => $shopeeQty + $tiktokQty,
                'total_revenue' => $shopeeRevenue + $tiktokRevenue,
                'shopee_order_id' => $shopeeOrderIds,
                'tiktok_live_id' => $tiktokLiveIds,
                'shopee_quantity' => $shopeeQty > 0 ? $shopeeQty : null,
                'tiktok_quantity' => $tiktokQty > 0 ? $tiktokQty : null,
                'shopee_revenue' => $shopeeRevenue > 0 ? $shopeeRevenue : null,
                'tiktok_revenue' => $tiktokRevenue > 0 ? $tiktokRevenue : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        if ($skippedEmpty > 0) {
            Log::warning("Reconciliation: Skipped {$skippedEmpty} records with empty product names");
        }
        
        // Bulk insert dengan chunk untuk menghindari memory issue
        if (!empty($reconciledData)) {
            $chunks = array_chunk($reconciledData, 500);
            $totalInserted = 0;
            
            foreach ($chunks as $chunk) {
                ReconciledData::insert($chunk);
                $totalInserted += count($chunk);
            }
            
            Log::info("Reconciliation: Bulk inserted {$totalInserted} reconciled records");
        } else {
            Log::warning("Reconciliation: No reconciled data to insert");
        }
    }
}
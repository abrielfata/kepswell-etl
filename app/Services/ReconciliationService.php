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
            ->unique();
        
        Log::info("Reconciliation: Found " . $allProductNames->count() . " unique products");
        
        foreach ($allProductNames as $productName) {
            $shopeeData = $shopeeByProduct->get($productName);
            $tiktokData = $tiktokByProduct->get($productName);
            
            // Hitung total dari Shopee
            $shopeeQty = $shopeeData ? $shopeeData->sum('quantity') : 0;
            $shopeeRevenue = $shopeeData ? $shopeeData->sum('total') : 0;
            $shopeeOrderIds = $shopeeData ? $shopeeData->pluck('order_id')->implode(',') : null;
            
            // Hitung total dari TikTok
            $tiktokQty = $tiktokData ? $tiktokData->sum('product_sold') : 0;
            $tiktokRevenue = $tiktokData ? $tiktokData->sum('revenue') : 0;
            $tiktokLiveIds = $tiktokData ? $tiktokData->pluck('live_id')->unique()->implode(',') : null;
            
            // Simpan ke reconciled_data
            ReconciledData::create([
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
            ]);
        }
        
        Log::info("Reconciliation: Created " . $allProductNames->count() . " reconciled records");
    }
}
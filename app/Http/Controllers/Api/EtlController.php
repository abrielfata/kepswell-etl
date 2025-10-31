<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtlBatch;
use App\Jobs\ProcessEtlBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EtlController extends Controller
{
    /**
     * Upload files dan start ETL process
     * 
     * POST /api/etl/upload
     * Body: batch_name, shopee_file, tiktok_file
     */
    public function upload(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'batch_name' => 'required|string|max:255',
            'shopee_file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
            'tiktok_file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Simpan file ke storage/app/uploads
            $shopeeFileName = time() . '_shopee.' . $request->file('shopee_file')->getClientOriginalExtension();
            $tiktokFileName = time() . '_tiktok.' . $request->file('tiktok_file')->getClientOriginalExtension();
            
            $request->file('shopee_file')->storeAs('uploads', $shopeeFileName);
            $request->file('tiktok_file')->storeAs('uploads', $tiktokFileName);

            // Buat batch record
            $batch = EtlBatch::create([
                'user_id' => 1, // TODO: Nanti ganti dengan auth()->id() setelah ada login
                'batch_name' => $request->batch_name,
                'status' => 'pending',
                'shopee_file' => $shopeeFileName,
                'tiktok_file' => $tiktokFileName,
            ]);

            // Dispatch job ke queue
            ProcessEtlBatch::dispatch($batch->id);

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully. ETL process started.',
                'data' => [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'status' => $batch->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch status
     * 
     * GET /api/etl/batch/{id}
     */
    public function getBatch($id)
    {
        $batch = EtlBatch::find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'created_at' => $batch->created_at,
                'processed_at' => $batch->processed_at,
                'error_message' => $batch->error_message,
            ]
        ]);
    }

    /**
     * Get reconciled data for a batch
     * 
     * GET /api/etl/batch/{id}/results
     */
    public function getResults($id)
    {
        $batch = EtlBatch::with('reconciledData')->find($id);

        if (!$batch) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'batch' => [
                    'id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'status' => $batch->status,
                ],
                'results' => $batch->reconciledData
            ]
        ]);
    }
}
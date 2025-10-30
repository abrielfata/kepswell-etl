<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Exception;

class ShopeeParserService
{
    /**
     * Parse file Shopee dan return array data
     * 
     * @param string $filePath Full path ke file (e.g. storage/app/uploads/shopee.csv)
     * @return array Array of records: [['order_id' => 'SH001', 'product_name' => '...', ...], ...]
     */
    public function parse(string $filePath): array
    {
        try {
            // Load file dengan PhpSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Baris pertama = header
            $headers = array_shift($rows);
            
            // Normalize header (lowercase, remove spaces)
            $headers = array_map(function($header) {
                return strtolower(trim(str_replace(' ', '_', $header)));
            }, $headers);
            
            $result = [];
            
            foreach ($rows as $row) {
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Combine header dengan data
                $record = array_combine($headers, $row);
                
                // Transform: bersihkan data
                $result[] = [
                    'order_id' => $record['order_id'] ?? null,
                    'product_name' => $this->normalizeProductName($record['product_name'] ?? ''),
                    'quantity' => (int) ($record['quantity'] ?? 0),
                    'price' => (float) ($record['price'] ?? 0),
                    'total' => (float) ($record['total'] ?? 0),
                    'order_date' => $record['order_date'] ?? now(),
                    'raw_data' => $record, // Simpan data mentah juga
                ];
            }
            
            Log::info("Shopee Parser: Parsed " . count($result) . " records");
            
            return $result;
            
        } catch (Exception $e) {
            Log::error("Shopee Parser Error: " . $e->getMessage());
            throw new Exception("Failed to parse Shopee file: " . $e->getMessage());
        }
    }
    
    /**
     * Normalize nama produk (lowercase, trim, remove extra spaces)
     */
    private function normalizeProductName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name); // Replace multiple spaces jadi 1 space
        return $name;
    }
}
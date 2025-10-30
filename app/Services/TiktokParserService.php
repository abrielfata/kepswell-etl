<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Exception;

class TiktokParserService
{
    /**
     * Parse file TikTok dan return array data
     * 
     * @param string $filePath Full path ke file
     * @return array Array of records
     */
    public function parse(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Baris pertama = header
            $headers = array_shift($rows);
            
            // Normalize header
            $headers = array_map(function($header) {
                return strtolower(trim(str_replace(' ', '_', $header)));
            }, $headers);
            
            $result = [];
            
            foreach ($rows as $row) {
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $record = array_combine($headers, $row);
                
                // Transform data TikTok
                $result[] = [
                    'live_id' => $record['live_id'] ?? null,
                    'host_name' => $record['host_name'] ?? null,
                    'product_name' => $this->normalizeProductName($record['product_name'] ?? ''),
                    'product_sold' => (int) ($record['product_sold'] ?? 0),
                    'revenue' => (float) ($record['revenue'] ?? 0),
                    'live_date' => $record['live_date'] ?? now(),
                    'raw_data' => $record,
                ];
            }
            
            Log::info("TikTok Parser: Parsed " . count($result) . " records");
            
            return $result;
            
        } catch (Exception $e) {
            Log::error("TikTok Parser Error: " . $e->getMessage());
            throw new Exception("Failed to parse TikTok file: " . $e->getMessage());
        }
    }
    
    /**
     * Normalize nama produk (sama seperti Shopee)
     */
    private function normalizeProductName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
}
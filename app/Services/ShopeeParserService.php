<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

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
            // Validasi file exists
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            // Load file dengan PhpSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Validasi file tidak kosong
            if (empty($rows)) {
                throw new Exception("File is empty");
            }
            
            // Baris pertama = header
            $headers = array_shift($rows);
            
            // Validasi header tidak kosong
            if (empty($headers)) {
                throw new Exception("Header row is empty");
            }
            
            // Normalize header (lowercase, remove spaces)
            $headers = array_map(function($header) {
                return strtolower(trim(str_replace(' ', '_', $header)));
            }, $headers);
            
            $result = [];
            $rowNumber = 1; // Untuk tracking error
            
            foreach ($rows as $row) {
                $rowNumber++;
                
                // Skip baris kosong
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Validasi panjang row sama dengan header
                if (count($headers) !== count($row)) {
                    Log::warning("Shopee Parser: Row #{$rowNumber} has mismatched column count. Expected: " . count($headers) . ", Got: " . count($row));
                    // Pad atau trim row untuk match header
                    if (count($row) < count($headers)) {
                        $row = array_pad($row, count($headers), null);
                    } else {
                        $row = array_slice($row, 0, count($headers));
                    }
                }
                
                // Combine header dengan data
                $record = array_combine($headers, $row);
                
                // Validasi combine berhasil
                if ($record === false) {
                    Log::warning("Shopee Parser: Failed to combine row #{$rowNumber}");
                    continue;
                }
                
                // Transform: bersihkan data
                $result[] = [
                    'order_id' => $record['order_id'] ?? null,
                    'product_name' => $this->normalizeProductName($record['product_name'] ?? ''),
                    'quantity' => (int) ($record['quantity'] ?? 0),
                    'price' => (float) ($record['price'] ?? 0),
                    'total' => (float) ($record['total'] ?? 0),
                    'order_date' => $this->parseDate($record['order_date'] ?? null),
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
    
    /**
     * Parse date string ke format database
     */
    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            // Handle Excel date serial number
            if (is_numeric($date)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                return Carbon::instance($date)->format('Y-m-d H:i:s');
            }
            
            // Parse string date
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning("Shopee Parser: Failed to parse date '{$date}': " . $e->getMessage());
            return null;
        }
    }
}
<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

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
            // Validasi file exists
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

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
            
            // Normalize header
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
                    Log::warning("TikTok Parser: Row #{$rowNumber} has mismatched column count. Expected: " . count($headers) . ", Got: " . count($row));
                    // Pad atau trim row untuk match header
                    if (count($row) < count($headers)) {
                        $row = array_pad($row, count($headers), null);
                    } else {
                        $row = array_slice($row, 0, count($headers));
                    }
                }
                
                $record = array_combine($headers, $row);
                
                // Validasi combine berhasil
                if ($record === false) {
                    Log::warning("TikTok Parser: Failed to combine row #{$rowNumber}");
                    continue;
                }
                
                // Transform data TikTok
                $result[] = [
                    'live_id' => $record['live_id'] ?? null,
                    'host_name' => $record['host_name'] ?? null,
                    'product_name' => $this->normalizeProductName($record['product_name'] ?? ''),
                    'product_sold' => (int) ($record['product_sold'] ?? 0),
                    'revenue' => (float) ($record['revenue'] ?? 0),
                    'live_date' => $this->parseDate($record['live_date'] ?? null),
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
            Log::warning("TikTok Parser: Failed to parse date '{$date}': " . $e->getMessage());
            return null;
        }
    }
}
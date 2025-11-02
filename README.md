# Kepswell-ETL

Sistem ETL (Extract, Transform, Load) untuk rekonsiliasi data penjualan dari marketplace Shopee dan TikTok Shop menggunakan Laravel Framework.

## 📋 Deskripsi Proyek

Kepswell-ETL adalah aplikasi backend yang dirancang untuk mengintegrasikan dan merekonsiliasi data penjualan dari dua marketplace (Shopee dan TikTok Shop) menjadi satu dataset terpadu berdasarkan nama produk. Sistem ini memungkinkan analisis dan pelaporan penjualan secara terpusat dari berbagai channel.

## ✨ Fitur Utama

- 📤 **Upload File Multi-Format**: Menerima file CSV dan Excel (XLSX, XLS) dari Shopee dan TikTok
- 🔄 **Proses ETL Otomatis**: Extract, Transform, dan Load data secara otomatis melalui background job queue
- 🔗 **Rekonsiliasi Data**: Menggabungkan data dari kedua marketplace berdasarkan nama produk
- 📊 **Tracking Status**: Memantau status proses ETL secara real-time (pending, processing, completed, failed)
- 🚀 **High Performance**: Menggunakan bulk insert dan chunking untuk menangani file besar dengan efisien
- 🔒 **Data Integrity**: Menggunakan database transaction untuk memastikan konsistensi data
- 📝 **Error Handling**: Logging dan error tracking yang comprehensive

## 🛠️ Teknologi

- **Framework**: Laravel 10.x
- **PHP**: 8.1+
- **Database**: MySQL/PostgreSQL/SQLite (sesuai konfigurasi)
- **Queue**: Laravel Queue (database/redis)
- **Library**: 
  - PhpSpreadsheet (untuk parsing Excel/CSV)
  - Laravel Sanctum (untuk API authentication - coming soon)

## 📦 Instalasi

### Prasyarat

- PHP >= 8.1
- Composer
- MySQL/PostgreSQL/SQLite
- Node.js & NPM (untuk frontend assets - jika diperlukan)

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone <repository-url>
   cd kepswell-etl
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi database di `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=kepswell_etl
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan migrations**
   ```bash
   php artisan migrate
   ```

6. **Setup storage link (opsional)**
   ```bash
   php artisan storage:link
   ```

7. **Konfigurasi queue (jika menggunakan database queue)**
   ```bash
   php artisan queue:table
   php artisan migrate
   ```

## 🚀 Penggunaan

### Menjalankan Development Server

```bash
php artisan serve
```

Server akan berjalan di `http://localhost:8000`

### Menjalankan Queue Worker

Proses ETL berjalan di background melalui queue. Pastikan queue worker berjalan:

```bash
php artisan queue:work
```

Atau untuk production dengan supervisor:

```bash
php artisan queue:work --daemon
```

## 📡 API Endpoints

### 1. Upload File dan Start ETL Process

```http
POST /api/etl/upload
Content-Type: multipart/form-data
```

**Request Body:**
- `batch_name` (string, required): Nama batch untuk identifikasi
- `shopee_file` (file, required): File dari Shopee (CSV/XLSX/XLS, max 10MB)
- `tiktok_file` (file, required): File dari TikTok (CSV/XLSX/XLS, max 10MB)

**Response (201):**
```json
{
  "success": true,
  "message": "Files uploaded successfully. ETL process started.",
  "data": {
    "batch_id": 1,
    "batch_name": "Batch Januari 2024",
    "status": "pending"
  }
}
```

### 2. Get Batch Status

```http
GET /api/etl/batch/{id}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "batch_name": "Batch Januari 2024",
    "status": "completed",
    "created_at": "2024-01-15T10:00:00.000000Z",
    "processed_at": "2024-01-15T10:05:00.000000Z",
    "error_message": null
  }
}
```

### 3. Get Reconciliation Results

```http
GET /api/etl/batch/{id}/results
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "batch": {
      "id": 1,
      "batch_name": "Batch Januari 2024",
      "status": "completed"
    },
    "results": [
      {
        "id": 1,
        "product_name": "kemeja casual",
        "total_quantity": 150,
        "total_revenue": 2250000.00,
        "shopee_quantity": 100,
        "tiktok_quantity": 50,
        "shopee_revenue": 1500000.00,
        "tiktok_revenue": 750000.00,
        "shopee_order_id": "SH001,SH002,SH003",
        "tiktok_live_id": "TT001,TT002"
      }
    ]
  }
}
```

## 📁 Struktur Proyek

```
kepswell-etl/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── EtlController.php      # API Controller
│   ├── Jobs/
│   │   └── ProcessEtlBatch.php           # Background job untuk ETL
│   ├── Models/
│   │   ├── EtlBatch.php                  # Model batch
│   │   ├── RawShopee.php                 # Model data mentah Shopee
│   │   ├── RawTiktok.php                 # Model data mentah TikTok
│   │   └── ReconciledData.php           # Model data rekonsiliasi
│   └── Services/
│       ├── ShopeeParserService.php       # Parser untuk file Shopee
│       ├── TiktokParserService.php       # Parser untuk file TikTok
│       └── ReconciliationService.php     # Service rekonsiliasi data
├── database/
│   └── migrations/                       # Database migrations
├── storage/
│   └── app/
│       └── uploads/                      # File upload disimpan di sini
└── routes/
    └── api.php                           # API routes
```

## 🔄 Alur Proses ETL

1. **Upload**: User mengupload file Shopee dan TikTok melalui API
2. **Extract**: File di-parse menggunakan PhpSpreadsheet
3. **Transform**: 
   - Normalisasi header (lowercase, underscore)
   - Normalisasi nama produk (lowercase, trim, remove extra spaces)
   - Parse dan format tanggal
   - Type casting (int, float)
4. **Load**: Data mentah disimpan ke database (`raw_shopee`, `raw_tiktok`)
5. **Reconcile**: Data digabungkan berdasarkan `product_name`
   - Group by product_name
   - Sum quantity dan revenue per produk
   - Simpan hasil ke `reconciled_data`

## 📊 Database Schema

### etl_batches
- `id`: Primary key
- `user_id`: Foreign key ke users
- `batch_name`: Nama batch
- `status`: Enum (pending, processing, completed, failed)
- `shopee_file`: Nama file Shopee
- `tiktok_file`: Nama file TikTok
- `error_message`: Pesan error jika gagal
- `processed_at`: Timestamp ketika selesai
- `created_at`, `updated_at`

### raw_shopee
- `id`: Primary key
- `batch_id`: Foreign key ke etl_batches
- `order_id`: ID order dari Shopee
- `product_name`: Nama produk (normalized)
- `quantity`: Jumlah
- `price`: Harga satuan
- `total`: Total harga
- `order_date`: Tanggal order
- `raw_data`: JSON data mentah
- `created_at`, `updated_at`

### raw_tiktok
- `id`: Primary key
- `batch_id`: Foreign key ke etl_batches
- `live_id`: ID live TikTok
- `host_name`: Nama host
- `product_name`: Nama produk (normalized)
- `product_sold`: Jumlah terjual
- `revenue`: Revenue
- `live_date`: Tanggal live
- `raw_data`: JSON data mentah
- `created_at`, `updated_at`

### reconciled_data
- `id`: Primary key
- `batch_id`: Foreign key ke etl_batches
- `product_name`: Nama produk
- `total_quantity`: Total quantity (Shopee + TikTok)
- `total_revenue`: Total revenue (Shopee + TikTok)
- `shopee_quantity`: Quantity dari Shopee saja
- `tiktok_quantity`: Quantity dari TikTok saja
- `shopee_revenue`: Revenue dari Shopee saja
- `tiktok_revenue`: Revenue dari TikTok saja
- `shopee_order_id`: ID order Shopee (comma-separated)
- `tiktok_live_id`: ID live TikTok (comma-separated)
- `created_at`, `updated_at`

## 🔧 Konfigurasi

### Queue Configuration

Default menggunakan `database` queue. Pastikan queue worker berjalan:

```bash
php artisan queue:work
```

Untuk production, disarankan menggunakan Redis:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### File Upload Configuration

Maksimal ukuran file default adalah 10MB. Ubah di `config/filesystems.php` jika diperlukan.

### Timeout Configuration

Job timeout default adalah 5 menit (300 detik). Dapat diubah di `ProcessEtlBatch.php`:

```php
public $timeout = 600; // 10 menit
```

## 🐛 Troubleshooting

### Queue tidak jalan

Pastikan queue worker berjalan:
```bash
php artisan queue:work --verbose
```

### File upload gagal

- Cek permission folder `storage/app/uploads`
- Cek ukuran file (max 10MB)
- Cek format file (hanya CSV/XLSX/XLS)

### ETL process gagal

- Cek log di `storage/logs/laravel.log`
- Pastikan struktur file sesuai format yang diharapkan
- Cek database connection

## 🔐 Security Notes

⚠️ **Catatan Penting**: Autentikasi API belum diimplementasikan. Disarankan untuk menambahkan middleware authentication sebelum digunakan di production.

## 📝 Format File yang Didukung

### Format Shopee
File harus memiliki header dengan kolom (case-insensitive):
- Order ID / order_id
- Product Name / product_name
- Quantity / quantity
- Price / price
- Total / total
- Order Date / order_date

### Format TikTok
File harus memiliki header dengan kolom (case-insensitive):
- Live ID / live_id
- Host Name / host_name
- Product Name / product_name
- Product Sold / product_sold
- Revenue / revenue
- Live Date / live_date

## 🧪 Testing

```bash
php artisan test
```

## 📄 License

Proyek ini menggunakan MIT License.

## 👤 Author

Dikembangkan untuk keperluan skripsi.

## 🙏 Acknowledgments

- Laravel Framework
- PhpSpreadsheet untuk file parsing

---

**Note**: Proyek ini masih dalam tahap pengembangan aktif. Fitur autentikasi dan security akan ditambahkan pada fase selanjutnya.
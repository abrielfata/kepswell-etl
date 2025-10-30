<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciledData extends Model
{
    use HasFactory;

    protected $table = 'reconciled_data';

    protected $fillable = [
        'batch_id',
        'product_name',
        'total_quantity',
        'total_revenue',
        'shopee_order_id',
        'tiktok_live_id',
        'shopee_quantity',
        'tiktok_quantity',
        'shopee_revenue',
        'tiktok_revenue',
    ];

    // Relasi: many reconciled records belong to 1 batch
    public function batch(): BelongsTo
    {
        return $this->belongsTo(EtlBatch::class, 'batch_id');
    }
}
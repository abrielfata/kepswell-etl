<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawShopee extends Model
{
    use HasFactory;

    protected $table = 'raw_shopee';

    protected $fillable = [
        'batch_id',
        'order_id',
        'product_name',
        'quantity',
        'price',
        'total',
        'order_date',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'order_date' => 'datetime',
    ];

    // Relasi: many raw_shopee records belong to 1 batch
    public function batch(): BelongsTo
    {
        return $this->belongsTo(EtlBatch::class, 'batch_id');
    }
}
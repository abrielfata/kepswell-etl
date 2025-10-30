<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawTiktok extends Model
{
    use HasFactory;

    protected $table = 'raw_tiktok';

    protected $fillable = [
        'batch_id',
        'live_id',
        'host_name',
        'product_name',
        'product_sold',
        'revenue',
        'live_date',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'live_date' => 'datetime',
    ];

    // Relasi: many raw_tiktok records belong to 1 batch
    public function batch(): BelongsTo
    {
        return $this->belongsTo(EtlBatch::class, 'batch_id');
    }
}
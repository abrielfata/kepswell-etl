<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtlBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'batch_name',
        'status',
        'shopee_file',
        'tiktok_file',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    // Relasi: 1 batch dimiliki oleh 1 user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: 1 batch has many raw_shopee records
    public function rawShopee(): HasMany
    {
        return $this->hasMany(RawShopee::class, 'batch_id');
    }

    // Relasi: 1 batch has many raw_tiktok records
    public function rawTiktok(): HasMany
    {
        return $this->hasMany(RawTiktok::class, 'batch_id');
    }

    // Relasi: 1 batch has many reconciled_data records
    public function reconciledData(): HasMany
    {
        return $this->hasMany(ReconciledData::class, 'batch_id');
    }
}
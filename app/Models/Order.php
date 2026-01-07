<?php

namespace App\Models;

use App\Enums\Side as SideEnum;
use App\Enums\Status as StatusEnum;
use App\Enums\Symbol as SymbolEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'symbol_id',
        'side_id',
        'price',
        'amount',
        'status_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'amount' => 'decimal:8',
            'side_id' => SideEnum::class,
            'status_id' => StatusEnum::class,
            'symbol_id' => SymbolEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function symbol(): BelongsTo
    {
        return $this->belongsTo(Symbol::class);
    }

    public function side(): BelongsTo
    {
        return $this->belongsTo(Side::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function buyerTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'buyer_order_id');
    }

    public function sellerTrades(): HasMany
    {
        return $this->hasMany(Trade::class, 'seller_order_id');
    }
}
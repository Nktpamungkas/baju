<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shipping_address' => 'array',
        'shipping_option'  => 'array',
        'shipping_cost'    => 'integer',
        'subtotal'         => 'integer',
        'discount_amount'  => 'integer',
        'total'            => 'integer',
        'paid_at'          => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'tracking_token';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRateQuote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'lat'     => 'float',
        'lng'     => 'float',
        'weight'  => 'integer',
        'options' => 'array',
    ];
}

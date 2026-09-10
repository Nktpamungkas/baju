<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value'        => 'integer',
        'min_subtotal' => 'integer',
        'active'       => 'boolean',
        'featured'     => 'boolean',
        'expires_at'   => 'datetime',
    ];
}

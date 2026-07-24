<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // slug string id, tidak auto-increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'variants' => 'array',
        'sizeCols' => 'array',
        'sizes'    => 'array',
        'price'    => 'integer',
    ];

    // Kirim ke Inertia dengan bentuk sama seperti data/products.json
    protected $hidden = ['created_at', 'updated_at'];
}

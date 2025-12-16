<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBooker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ob_code',
        'van',
        'status',
    ];
}

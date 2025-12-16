<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'van',
        'shop_name',
        'address',
        'sub_address',
        'phone',
        'category',
        'channel',
        'ntn_number',
        'cnic',
        'sales_tax_number',
        'distribution',
        'day',
        'status',
        'adv_tax_percent',
        'percentage'
    ];
    //
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'dms_code',
        'brand',
        'list_price_before_tax',
        'fed_tax_percent',
        'fed_sales_tax',
        'net_list_price',
        'distribution_margin',
        'distribution_manager_percent',
        'trade_price_before_tax',
        'fed_2',
        'sales_tax_3',
        'net_trade_price',
        'retailer_margin',
        'retailer_margin_4',
        'consumer_price_before_tax',
        'fed_5',
        'sales_tax_6',
        'net_consumer_price',
        'total_margin',
        'unit_price',
        'packing',
        'packing_one',
        'reorder_level',
        'type',
        'sku',
        'description',
        'price',
        'stock_quantity',
        'category',
    ];
    //
}

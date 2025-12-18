<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'value', 'atl', 'adv_tax_percent'];
}

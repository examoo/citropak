<?php

namespace App\Models;

use App\Models\Traits\BaseTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'conversion',
        'units',
        'status',
    ];
}

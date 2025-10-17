<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_item';
    public $incrementing = false; // ID manual
    protected $keyType = 'string';

    protected $fillable = [
        'id_item',
        'name_item',
        'quantity',
        'capital_price',
        'selling_price',
    ];
}

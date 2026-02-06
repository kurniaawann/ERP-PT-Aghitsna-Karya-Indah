<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relasi ke Employee
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'division', 'name');
    }
}

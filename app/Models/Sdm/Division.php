<?php

namespace App\Models\Sdm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel divisions.
 *
 * Menyimpan data divisi/departemen perusahaan.
 * Relasi ke Employee menggunakan kolom `division` (nama divisi)
 *而不是 foreign key.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 */
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

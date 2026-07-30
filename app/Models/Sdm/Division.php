<?php

namespace App\Models\Sdm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk tabel divisions.
 *
 * Menyimpan data divisi/departemen perusahaan.
 * Menggunakan relasi berbasis string dengan Employee melalui kolom `division`
 * (mencocokkan nama divisi) bukan foreign key tradisional.
 *
 * @property int    $id
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read int $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sdm\Employee[] $employees
 */
class Division extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    /**
     * Mendapatkan karyawan yang termasuk dalam divisi ini.
     *
     * Menggunakan relasi berbasis string: employees.division = divisions.name.
     *
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'division', 'name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

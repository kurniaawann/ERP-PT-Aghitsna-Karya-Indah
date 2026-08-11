<?php

namespace App\Models\Sdm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk tabel executives.
 *
 * Menyimpan data petinggi/pimpinan perusahaan beserta gambar tanda tangan.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $position
 * @property string|null $signature_image
 * @property string|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Executive extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'position',
        'signature_image',
        'created_by',
    ];

    /**
     * Mendapatkan user pembuat data petinggi ini.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models\Administrasi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentReceipt extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_document';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_document',
        'received_from',
        'regarding',
        'form_of',
        'receipt_date',
        'receipt_time',
        'location',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    /**
     * Generate kode dokumen berikutnya (DOC-001, DOC-002, dst)
     */
    public static function generateDocumentCode()
    {
        $lastDocument = self::orderBy('id_document', 'desc')->first();

        if (!$lastDocument) {
            return 'DOC-001';
        }

        // Ambil nomor dari kode terakhir (contoh: DOC-001 -> 001)
        $lastNumber = (int) substr($lastDocument->id_document, 4);
        $newNumber = $lastNumber + 1;

        // Format dengan 3 digit (001, 002, ..., 999, 1000, dst)
        return 'DOC-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}

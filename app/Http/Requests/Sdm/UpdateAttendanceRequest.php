<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request untuk pembaruan data absensi.
 *
 * Memvalidasi karyawan, tanggal, status, dan catatan sebelum data absensi
 * diperbarui di basis data.
 */
class UpdateAttendanceRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna berwenang melakukan permintaan ini.
     *
     * Otorisasi ditangani di level route melalui middleware('role:admin').
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk permintaan ini.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|string|exists:employees,employee_code',
            'attendance_date' => 'required|date|before_or_equal:today',
            'status' => 'required|string|in:hadir,izin,sakit,cuti',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Mendapatkan pesan validasi kustom.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan tidak boleh kosong.',
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid.',
            'attendance_date.required' => 'Tanggal tidak boleh kosong.',
            'attendance_date.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',
            'status.required' => 'Status tidak boleh kosong.',
            'status.in' => 'Status harus salah satu dari: Hadir, Izin, Sakit, Cuti.',
        ];
    }
}

<?php

namespace App\Http\Requests\Sdm;

use App\Services\InputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Form request untuk penyimpanan data lembur baru.
 *
 * Memvalidasi pemilihan karyawan, tanggal, jam lembur, tarif, dan catatan
 * sebelum data lembur disimpan ke basis data.
 */
class StoreOvertimeRequest extends FormRequest
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
            'overtime_hours' => 'required|numeric|min:0.5|max:24',
            'overtime_rate' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Validasi tambahan: menolak lembur pada hari Minggu (hari libur).
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$validator->errors()->has('attendance_date') && $this->attendance_date) {
                $date = Carbon::parse($this->attendance_date);

                if ($date->dayOfWeek === Carbon::SUNDAY) {
                    $validator->errors()->add(
                        'attendance_date',
                        'Tidak dapat menambah lembur pada hari Minggu karena Minggu adalah hari libur.'
                    );
                }
            }
        });
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
            'overtime_hours.required' => 'Jam lembur tidak boleh kosong.',
            'overtime_hours.numeric' => 'Jam lembur harus berupa angka.',
            'overtime_hours.min' => 'Jam lembur minimal 0.5 jam.',
            'overtime_hours.max' => 'Jam lembur maksimal 24 jam.',
            'overtime_rate.required' => 'Tarif tidak boleh kosong.',
            'overtime_rate.integer' => 'Tarif harus berupa angka bulat.',
            'overtime_rate.min' => 'Tarif tidak boleh negatif.',
            'notes.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }

    /**
     * Mempersiapkan data sebelum validasi.
     *
     * Menormalisasi tarif lembur dari format string mata uang (misalnya "15.000")
     * menjadi integer (misalnya 15000) sebelum aturan validasi diterapkan.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'overtime_rate' => InputNormalizer::normalizeCurrency($this->overtime_rate),
        ]);
    }
}

<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for storing bulk attendance records.
 *
 * Validates employee selection, date range, status, and notes
 * before the attendance data is persisted to the database.
 */
class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the route level via middleware('role:admin').
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|string|exists:employees,employee_code',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'status' => 'required|string|in:hadir,izin,sakit,cuti',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_ids.required' => 'Silakan pilih minimal 1 karyawan.',
            'employee_ids.min' => 'Silakan pilih minimal 1 karyawan.',
            'employee_ids.*.exists' => 'Karyawan yang dipilih tidak valid.',
            'start_date.required' => 'Tanggal mulai tidak boleh kosong.',
            'start_date.before_or_equal' => 'Tanggal mulai tidak boleh lebih dari hari ini.',
            'end_date.required' => 'Tanggal akhir tidak boleh kosong.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh lebih kecil dari tanggal mulai.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh lebih dari hari ini.',
            'status.required' => 'Status tidak boleh kosong.',
            'status.in' => 'Status harus salah satu dari: Hadir, Izin, Sakit, Cuti.',
        ];
    }
}

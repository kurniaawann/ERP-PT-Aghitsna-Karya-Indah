<?php

namespace App\Http\Requests\Sdm;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating a single attendance record.
 *
 * Validates employee, date, status, and notes before the attendance
 * data is updated in the database.
 */
class UpdateAttendanceRequest extends FormRequest
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
            'employee_id' => 'required|string|exists:employees,employee_code',
            'attendance_date' => 'required|date|before_or_equal:today',
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
            'employee_id.required' => 'Karyawan tidak boleh kosong.',
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid.',
            'attendance_date.required' => 'Tanggal tidak boleh kosong.',
            'attendance_date.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',
            'status.required' => 'Status tidak boleh kosong.',
            'status.in' => 'Status harus salah satu dari: Hadir, Izin, Sakit, Cuti.',
        ];
    }
}

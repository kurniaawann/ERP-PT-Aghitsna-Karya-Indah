<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest untuk validasi pembaruan status massal reminder.
 *
 * Memastikan parameter yang dikirim valid sebelum diproses oleh Service.
 */
class BulkUpdateReminderStatusRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki otorisasi untuk melakukan request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:invoice_proyek_reminders,id'],
            'status' => ['required', 'in:pending,notified,paid'],
        ];
    }
}

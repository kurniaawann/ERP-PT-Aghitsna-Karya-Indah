<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest untuk validasi pembaruan status satu reminder.
 *
 * Memastikan parameter yang dikirim valid sebelum diproses oleh Service.
 */
class UpdateReminderStatusRequest extends FormRequest
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
            'status' => ['required', 'in:pending,notified,paid'],
        ];
    }
}

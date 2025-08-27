<?php

namespace App\Http\Requests\Employee\Timelogs;

use Illuminate\Foundation\Http\FormRequest;

class CheckInOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_time'    => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'The user is required.',
            'user_id.exists'   => 'The selected user does not exist.',
            'date_time.required' => 'The time log entry needs a date and time.',
            'date_time.date'     => 'The date and time must be a valid datetime format.',
        ];
    }
}

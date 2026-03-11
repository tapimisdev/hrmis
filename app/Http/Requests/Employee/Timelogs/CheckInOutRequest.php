<?php

namespace App\Http\Requests\Employee\Timelogs;

use App\Enums\FnEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'type' => [
                'nullable',
                Rule::in(FnEnum::cases()),
            ],
            'accomplishment' => [
                'required_if:type,1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'The user is required.',
            'user_id.exists'   => 'The selected user does not exist.',
            'date_time.required' => 'The time log entry needs a date and time.',
            'date_time.date'     => 'The date and time must be a valid datetime format.',
            'date_time.after_or_equal' => 'The date and time cannot be in the past.',
            'date_time.before_or_equal' => 'The date and time cannot be in the future.'
        ];
    }
}

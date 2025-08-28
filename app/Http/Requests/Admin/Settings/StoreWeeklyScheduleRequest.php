<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeeklyScheduleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('weekly_schedules', 'name')->ignore($this->route('weekly_schedule')),
            ],
            'is_monday' => 'required|boolean',
            'is_tuesday' => 'required|boolean',
            'is_wednesday' => 'required|boolean',
            'is_thursday' => 'required|boolean',
            'is_friday' => 'required|boolean',
            'is_saturday' => 'required|boolean',
            'is_sunday' => 'required|boolean',
        ];
    }
}

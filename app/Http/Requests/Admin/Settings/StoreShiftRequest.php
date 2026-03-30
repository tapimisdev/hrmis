<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
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
                Rule::unique('shifts', 'name')
                    ->where(fn ($query) => $query->where('is_active', true))
                    ->ignore($this->shift), // for update
            ],

            // Flexible shift logic
            'earliest_time' => 'required_if:is_flexible,1|nullable|date_format:H:i',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required_unless:is_flexible,1|nullable|date_format:H:i|after:start_time',

            // Overtime
            'minimum_overtime_hours' => 'nullable|numeric|min:0',
            'working_hours' => 'required|numeric|min:0',

            // Breaks
            'break_out_time' => 'required_if:is_break_required,1|date_format:H:i',
            'break_in_time'  => 'required_if:is_break_required,1|date_format:H:i|after:break_out_time',

            // Flags
            'is_break_required' => 'required|boolean',
            'is_night_shift'    => 'required|boolean',
            'is_flexible'  => 'required|boolean',
            'grace_period' => 'required_if:is_flexible,0|nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'end_time.required_unless' => 'This field is required',
        ];
    }

}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeLogsRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],

            // Times must be in proper format
            'time_in' => ['required', 'date_format:H:i:s'],
            'break_out' => ['required', 'date_format:H:i:s', 'after:time_in'],
            'break_in' => ['required', 'date_format:H:i:s', 'after:break_out'],
            'time_out' => ['required', 'date_format:H:i:s', 'after:time_in'],

            'overtime_in' => ['nullable', 'date_format:H:i:s', 'after:break_out'],
            'overtime_out' => [
                'required_with:overtime_in',   // only required if overtime_in is present
                'nullable',
                'date_format:H:i:s',
                'after:overtime_in'
            ],

            // Foreign key checks
            'shift' => ['required', 'exists:shifts,id'],
            'weeklyschedule' => ['required', 'exists:work_schedule,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $times = [
                'time_in' => $this->input('time_in'),
                'break_out' => $this->input('break_out'),
                'break_in' => $this->input('break_in'),
                'time_out' => $this->input('time_out'),
                'overtime_in' => $this->input('overtime_in'),
                'overtime_out' => $this->input('overtime_out'),
            ];

            // Filter out nulls so we only check provided fields
            $times = array_filter($times);

            // Sort times in chronological order
            $ordered = collect($times)->sort()->values();

            // Check if each pair has at least 10 seconds gap
            for ($i = 0; $i < count($ordered) - 1; $i++) {
                $first = \Carbon\Carbon::createFromFormat('H:i:s', $ordered[$i]);
                $second = \Carbon\Carbon::createFromFormat('H:i:s', $ordered[$i + 1]);

                if ($second->diffInSeconds($first) < 10) {
                    $validator->errors()->add(
                        'time_in',
                        'Each log must be at least 10 seconds apart.'
                    );
                    break;
                }
            }
        });
    }
}

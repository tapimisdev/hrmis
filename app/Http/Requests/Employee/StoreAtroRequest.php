<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAtroRequest extends FormRequest
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
        $userId = request('user_id') ?? Auth::id();

        return [
            'user_id' => ['nullable', 'exists:users,id'], // only for timekeeping adjustment only
            'date' => [
                'required',
                'date',
                Rule::unique('overtimes')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'total_hours' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['pending', 'approved'])], // only for timekeeping adjustment only
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->start_time && $this->end_time && $this->total_hours !== null) {
                // Convert times to Carbon instances
                $start = \Carbon\Carbon::createFromFormat('H:i', $this->start_time);
                $end = \Carbon\Carbon::createFromFormat('H:i', $this->end_time);

                // Calculate difference in hours
                $calculatedHours = $start->diffInMinutes($end) / 60;

                // Check if total_hours matches calculated hours
                if (round($this->total_hours, 2) != round($calculatedHours, 2)) {
                    $validator->errors()->add(
                        'total_hours',
                        "Total hours must match the difference between start and end time ({$calculatedHours} hours)."
                    );
                }
            }
        });
    }
}

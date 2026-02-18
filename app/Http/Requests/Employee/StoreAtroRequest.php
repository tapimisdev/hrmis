<?php

namespace App\Http\Requests\Employee;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'user_id' => ['nullable', 'exists:employee_information,employee_no'],

            'date' => [
                'required',
                'date',
                Rule::unique('overtime_applications')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)
                        ->whereNotIn('status', ['approved', 'cancelled']);
                }),
            ],

            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],

            'total_hours' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'reason' => ['required', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['pending', 'approved'])],

            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:8192'],
        ];
    }


    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            if ($this->start_time && $this->end_time && $this->total_hours !== null) {

                $start = Carbon::createFromFormat('H:i', $this->start_time);
                $end   = Carbon::createFromFormat('H:i', $this->end_time);

                $calculatedHours = $start->diffInMinutes($end) / 60;

                if (round($this->total_hours, 2) !== round($calculatedHours, 2)) {
                    $validator->errors()->add(
                        'total_hours',
                        "Total hours must match the difference between start and end time ({$calculatedHours} hours)."
                    );
                }
            }

            $errors = $validator->errors();

            if (
                $errors->has('attachments') ||
                collect($errors->keys())->contains(fn($k) => str_starts_with($k, 'attachments.'))
            ) {
                $messages = [];

                foreach ($errors->keys() as $key) {
                    if ($key === 'attachments' || str_starts_with($key, 'attachments.')) {
                        $messages = array_merge($messages, $errors->get($key));
                        $errors->forget($key);
                    }
                }

                // Keep only unique messages (and first if you want just one)
                $errors->add('attachments', array_values(array_unique($messages)));
            }
        });
    }
}

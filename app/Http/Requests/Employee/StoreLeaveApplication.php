<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreLeaveApplication extends FormRequest
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
            'user_id' => ['nullable', 'exists:users,id'],
            'leave_id' => ['required', 'exists:leaves,id'],
            'reason' => ['required', 'string', 'max:500'],
            'days' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],

            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:2048'],

            // Prevent duplicate leave applications with same dates and type
            Rule::unique('leave_applications')->where(function ($query) {
                return $query->where('employee_id', $this->employee_id)
                    ->where('leave_id', $this->leave_id)
                    ->where('start_date', $this->start_date)
                    ->where('end_date', $this->end_date);
            }),
        ];
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate Days vs Date Range
            if ($this->start_date && $this->end_date && $this->days) {
                $start = \Carbon\Carbon::parse($this->start_date);
                $end = \Carbon\Carbon::parse($this->end_date);

                // Inclusive difference (count start & end date)
                $expectedDays = $start->diffInDays($end) + 1;

                if ($this->days != $expectedDays) {
                    $validator->errors()->add('days', "Days must match the difference between start and end date ($expectedDays).");
                }
            }

            // Check for existing leave OR OB slip within range
            if ($this->start_date && $this->end_date && $this->user_id) {
                $start = $this->start_date;
                $end   = $this->end_date;

                // Check LEAVES
                $leaveExists = DB::table('leave_applications')
                    ->where('user_id', $this->user_id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($q) use ($start, $end) {
                                $q->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                            });
                    })
                    ->exists();

                // Check OB SLIPS
                $obExists = DB::table('obs')
                    ->where('user_id', $this->user_id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('date_from', [$start, $end])
                            ->orWhereBetween('date_to', [$start, $end])
                            ->orWhere(function ($q) use ($start, $end) {
                                $q->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                            });
                    })
                    ->exists();

                if ($leaveExists || $obExists) {
                    $validator->errors()->add(
                        'start_date',
                        'These dates overlap with an existing leave or OB slip.'
                    );
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'leave_id.required' => 'leave type is required.',
            'reason.required' => 'Reason for leave is required.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after_or_equal' => 'End date must be after or same as start date.',
            'end_date.required' => 'End date is required.',
            'leave_applications.unique' => 'This leave request already exists.',

            // Attachment messages
            'attachment.file' => 'The attachment must be a file.',
            'attachment.mimes' => 'The attachment must be a PDF, JPG, PNG, DOC, or DOCX file.',
            'attachment.max' => 'The attachment size must not exceed 2MB.',
        ];
    }

}

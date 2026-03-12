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
     */
    public function rules(): array
    {
        return [
            'type' => [
                'nullable',
                Rule::in(array_column(FnEnum::cases(), 'value')),
            ],
            'accomplishment' => [
                Rule::requiredIf(fn () => $this->requiresAccomplishment()),
            ],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user is required.',
            'user_id.exists'   => 'The selected user does not exist.',
            'date_time.required' => 'The time log entry needs a date and time.',
            'date_time.date'     => 'The date and time must be a valid datetime format.',
            'date_time.after_or_equal' => 'The date and time cannot be in the past.',
            'date_time.before_or_equal' => 'The date and time cannot be in the future.',
        ];
    }

    /**
     * Determine if accomplishment is required based on type and Web Time access.
     */
    private function requiresAccomplishment(): bool
    {
        // Must be type 1
        if ($this->input('type') != 1) {
            return false;
        }

        // Get employee number
        $employeeNo = $this->user()->employee_information->employee_no ?? null;
        if (!$employeeNo) {
            return false;
        }

        // Get latest Web Time access rule
        $rule = DB::table('web_time_access')
            ->where('employee_no', $employeeNo)
            ->where('effectivity_date', '<=', now())
            ->orderByDesc('effectivity_date')
            ->orderByDesc('id')
            ->first();

        if (!$rule) {
            return false;
        }

        $now   = Carbon::now();
        $today = $now->toDateString();
        $dow   = $now->format('D');

        // Always access
        if ((int)$rule->always === 1) {
            return (bool)$rule->isRequiredAccomplishment;
        }

        // Check specific dates
        $specificDates = json_decode($rule->specific_dates ?? '[]', true);
        $specificDates = is_array($specificDates) ? $specificDates : [];
        if (in_array($today, $specificDates, true)) {
            return (bool)$rule->isRequiredAccomplishment;
        }

        // Check days of week
        $daysOfWeek = json_decode($rule->days_of_week ?? '[]', true);
        $daysOfWeek = is_array($daysOfWeek) ? $daysOfWeek : [];
        if (in_array($dow, $daysOfWeek, true)) {
            return (bool)$rule->isRequiredAccomplishment;
        }

        return false;
    }
}
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
                Rule::in(FnEnum::cases()), // works with enum cases
            ]
        ];
    }

    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {

        //     $duplicateThreshold = 10; // seconds


        //     if($this->input('type') === 'timeOut') {
        //         $duplicateThreshold = 30; // seconds
        //     }


        //     // 1) Get all today's logs for this user
        //     $logs = DB::table('timelogs')
        //         ->where('user_id', $this->user()->id)
        //         ->whereDate('date_time', now()->toDateString())
        //         ->orderBy('date_time')
        //         ->get();

        //     // 2) Collapse duplicates (just like getValidLogs)
        //     $filtered = collect();
        //     foreach ($logs as $log) {
        //         if ($filtered->isEmpty()) {
        //             $filtered->push($log);
        //             continue;
        //         }

        //         $last = $filtered->last();
        //         $lastTime = Carbon::parse($last->date_time);
        //         $currTime = Carbon::parse($log->date_time);

        //         if ($currTime->diffInSeconds($lastTime) < $duplicateThreshold) {
        //             // skip duplicate (too close)
        //             continue;
        //         }

        //         $filtered->push($log);
        //     }

        //     // 3) Now compare the *new* log against the last "valid" one
        //     if ($filtered->isNotEmpty()) {
        //         $lastLog = $filtered->last();
        //         $lastTime = Carbon::parse($lastLog->date_time);
        //         $newTime  = Carbon::parse($this->date_time);

        //         if ($newTime->diffInSeconds($lastTime) < $duplicateThreshold) {
        //             $validator->errors()->add(
        //                 'date_time',
        //                 "Logs must be at least {$duplicateThreshold} seconds apart."
        //             );
        //         }
        //     }
        // });
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

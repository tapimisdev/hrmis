<div class="card shadow p-3">
    <div class="card-header bg-transparent">
        <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
            {{ $violation ? 'Edit Violation Rule' : 'Create Violation Rule' }}
        </h4>
    </div>
    <div class="card-body">
        <div class="row my-3">
            <div class="col-12 col-md-6 mb-3">
                <label class="mb-2" for="violation_type">Behavioral Type <span class="text-danger">*</span></label>
                <select
                    id="violation_type"
                    name="violation_type"
                    class="form-select"
                >
                    @php($selectedViolationType = old('violation_type', $violation->violation_type ?? ''))
                    <option value="">Select Behavioral Type</option>
                    @foreach([
                        'Tardiness / Late',
                        'Habitual Tardiness',
                        'Habitual Tardiness - Consecutive',
                        'Undertime',
                        'Frequent Undertime',
                        'Frequent Undertime - Consecutive',
                        'Unauthorized Absence',
                        'Habitual Absenteeism',
                        'Habitual Absenteeism - Consecutive',
                        'Discrepancy / Missing Timelog',
                        'Missed Break Log',
                    ] as $type)
                        <option value="{{ $type }}" @selected($selectedViolationType === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <div class="violation_type_error error-field"></div>
            </div>

            <div class="col-12 col-md-6 mb-3">
                <label class="mb-2" for="action_name">System Action / Sanction Text <span class="text-danger">*</span></label>
                <input
                    type="text"
                    id="action_name"
                    name="action_name"
                    class="form-control"
                    value="{{ old('action_name', $violation->action_name ?? '') }}"
                    list="violation-action-options"
                    placeholder="e.g. Habitual Tardiness"
                >
                <datalist id="violation-action-options">
                    <option value="Mark as Habitual Tardiness Candidate"></option>
                    <option value="Habitual Tardiness"></option>
                    <option value="Mark as Frequent Undertime Candidate"></option>
                    <option value="Frequent Undertime"></option>
                    <option value="Mark as Habitual Absenteeism Candidate"></option>
                    <option value="Habitual Absenteeism"></option>
                    <option value="Mark as Incomplete Timelog / For Explanation"></option>
                </datalist>
                <div class="action_name_error error-field"></div>
            </div>

            <div class="col-12 mb-3">
                <label class="mb-2" for="rule_trigger">Rule / Trigger <span class="text-danger">*</span></label>
                <textarea
                    id="rule_trigger"
                    name="rule_trigger"
                    class="form-control"
                    rows="3"
                    placeholder="Describe when this violation setting is triggered"
                >{{ old('rule_trigger', $violation->rule_trigger ?? '') }}</textarea>
                <div class="rule_trigger_error error-field"></div>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label class="mb-2" for="evaluation_period">Evaluation Period <span class="text-danger">*</span></label>
                @php($selectedEvaluationPeriod = old('evaluation_period', $violation->evaluation_period ?? ''))
                <select
                    id="evaluation_period"
                    name="evaluation_period"
                    class="form-select"
                >
                    <option value="">Select evaluation period</option>
                    @foreach([
                        'Count monthly',
                        'Jan–Jun or Jul–Dec',
                        'Jan–Dec',
                        'Daily / Monthly',
                        'Per incident',
                    ] as $period)
                        <option value="{{ $period }}" @selected($selectedEvaluationPeriod === $period)>{{ $period }}</option>
                    @endforeach
                </select>
                <div class="evaluation_period_error error-field"></div>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label class="mb-2" for="monthly_threshold">Monthly / Incident Count <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input
                        type="number"
                        min="0.01"
                        step="0.01"
                        id="monthly_threshold"
                        name="monthly_threshold"
                        class="form-control"
                        value="{{ old('monthly_threshold', $violation->monthly_threshold ?? $violation->threshold ?? 1) }}"
                    >
                    <span class="input-group-text">count</span>
                </div>
                <small class="text-muted">Example: 10 undertimes per month.</small>
                <div class="monthly_threshold_error error-field"></div>
            </div>

            <div class="col-12 col-md-4 mb-3">
                <label class="mb-2" for="threshold">Threshold <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input
                        type="number"
                        min="1"
                        id="threshold"
                        name="threshold"
                        class="form-control"
                        value="{{ old('threshold', $violation->threshold ?? 1) }}"
                    >
                    <span class="input-group-text">count</span>
                </div>
                <small class="text-muted">Example: 2 qualifying months.</small>
                <div class="threshold_error error-field"></div>
            </div>
        </div>
    </div>
    <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
        <button type="submit" id="submit-button" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
            {{ $submitLabel }}
        </button>
    </div>
</div>

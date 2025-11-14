@extends('admin.layouts.app')

@section('content')
    <div class="container pt-4 px-3">
        <x-header title="Update Employee Records" subtitle="Employee's personal data sheet and portal leave-credits">
            <x-button-link 
                :href="route('hris.employee.leave-credits', ['employee_no' => $employee_no])" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div class="row">
            <div class="col-12">
                @php
                    $latestYear = collect($data)->keys()->max();
                @endphp

                @if($data->isNotEmpty())
                    <form id="form-add-year" action="{{ route('hris.employee.leave-card.add_year', ['employee_no' => $employee_no, 'leave_id' => $leave_id]) }}" method="post">
                        @csrf
                        @method('put')
                        <div class="d-flex justify-content-end mb-4 mt-3">
                            <button type="submit" class="btn btn-primary text-uppercase fw-bold px-4 py-2" id="add-year-btn">Add Year</button>
                        </div>
                    </form>
                @endif

               <form id="form-save-changes" action="{{ route('hris.employee.leave-card.save', ['employee_no' => $employee_no, 'leave_id' => $leave_id]) }}" method="POST">
                    @csrf
                    <div class="accordion" id="accordion-leave-card">
                        @forelse($data as $year => $months)
                            @php
                                $isLatest = $year == $latestYear;
                                $previousYear = $year - 1;
                                $prevDecemberBalance = '0';

                                if(isset($data[$previousYear])) {
                                    $prevMonths = $data[$previousYear];
                                    $decemberRecord = $prevMonths->get('december')->first() ?? null;
                                    $prevDecemberBalance = $decemberRecord ? number_format($decemberRecord->balance, 2) : 0;
                                }
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header d-flex justify-content-between align-items-center" id="heading-{{ $year }}">
                                    <button class="accordion-button fw-bold text-uppercase {{ $isLatest ? '' : 'collapsed' }}" 
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $year }}"
                                        aria-expanded="{{ $isLatest ? 'true' : 'false' }}"
                                        aria-controls="collapse-{{ $year }}">
                                        {{ $year }} 
                                        @if($prevDecemberBalance > 0)
                                            <div class="d-block ms-5 text-danger">
                                                (Balance brought forward from {{$previousYear}}: {{$prevDecemberBalance}})
                                            </div>
                                        @endif

                                    </button>

                                    @if($isLatest)
                                        <button type="button" 
                                            class="btn btn-danger text-uppercase fw-bold m-2 remove-year-btn"
                                            data-url="{{ route('hris.employee.leave-card.remove_year', ['employee_no' => $employee_no, 'leave_id' => $leave_id, 'year' => $year]) }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </h2>

                                <div id="collapse-{{ $year }}" 
                                    class="accordion-collapse collapse {{ $isLatest ? 'show' : '' }}"
                                    aria-labelledby="heading-{{ $year }}"
                                    data-bs-parent="#accordion-leave-card">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle text-center">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2">Period</th>
                                                        <th rowspan="2">Particulars</th>
                                                        <th colspan="3">Vacation Leave</th>
                                                        <th rowspan="2">Remarks</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Earned</th>
                                                        <th>Deductions</th>
                                                        <th>Balance</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach($months as $month => $records)
                                                        @foreach($records as $record)
                                                            <tr>
                                                                <td class="text-uppercase">{{ $month }}</td>

                                                                <td>
                                                                    <textarea 
                                                                        name="records[{{ $record->year }}][{{ strtolower($month) }}][particulars]" 
                                                                        class="form-control" 
                                                                        style="width: 450px; height: 100px;"
                                                                    >{{ $record->particulars }}</textarea>
                                                                </td>

                                                                <td>
                                                                    <input 
                                                                        type="text" 
                                                                        name="records[{{ $record->year }}][{{ strtolower($month) }}][earned]" 
                                                                        class="form-control text-center restricted" 
                                                                        value="{{ number_format($record->earned, 2) }}" 
                                                                        readonly
                                                                    >
                                                                </td>

                                                                <td>
                                                                    <input 
                                                                        type="text" 
                                                                        name="records[{{ $record->year }}][{{ strtolower($month) }}][deduction]" 
                                                                        class="form-control text-center" 
                                                                        value="{{ number_format($record->deduction, 2) }}"
                                                                    >
                                                                </td>

                                                                <td>
                                                                    <input 
                                                                        type="text" 
                                                                        name="records[{{ $record->year }}][{{ strtolower($month) }}][balance]" 
                                                                        class="form-control text-center restricted" 
                                                                        value="{{ number_format($record->balance, 2) }}"
                                                                        readonly
                                                                    >
                                                                </td>

                                                                <td>
                                                                    <textarea 
                                                                        name="records[{{ $record->year }}][{{ strtolower($month) }}][remarks]" 
                                                                        class="form-control" 
                                                                        style="width: 300px; height: 100px;"
                                                                    >{{ $record->remarks }}</textarea>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-info text-uppercase fw-bold text-center my-5">No data to be shown</div>
                        @endforelse
                    </div>

                    @if($data->isNotEmpty())
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-3 py-2 text-uppercase fw-bold">Save Changes</button>
                        </div>
                    @endif
                </form>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function () {

        put($('#form-add-year').attr('action'), false, '#form-add-year');
        put($('#form-save-changes').attr('action'), false, '#form-save-changes');

        const ajaxAction = (url, method = 'PUT', successCallback) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _method: method,
                    _token: '{{ csrf_token() }}'
                },
                success: successCallback,
                error: () => alert('Failed to perform the action.')
            });
        };

        $(document).on('click', '.remove-year-btn', function() {
            const url = $(this).data('url');

            confirmAction(
                'Are you sure to continue?',
                'Once performed, this action is permanent and cannot be undone',
                'Yes, proceed!',
                () => ajaxAction(url, 'PUT', (response) => location.href = response.redirect
            ));
        });

        function recalculateBalances() {
            let prevBalance = 0;

            $('table tbody tr').each(function() {
                let $row = $(this);

                let earnedInput = $row.find('input[name*="[earned]"]');
                let deductionInput = $row.find('input[name*="[deduction]"]');
                let balanceInput = $row.find('input[name*="[balance]"]');

                let earned = parseFloat(earnedInput.val()) || 0;
                let deduction = parseFloat(deductionInput.val()) || 0;

                earnedInput.val(earned.toFixed(2));
                deductionInput.val(deduction.toFixed(2));

                let balance = prevBalance + earned - deduction;
                balanceInput.val(balance.toFixed(2));

                prevBalance = balance;
            });
        }

        $('table').on('input', 'input[name*="[deduction]"]', function() {
            recalculateBalances();
        });

    });
</script>
@endsection


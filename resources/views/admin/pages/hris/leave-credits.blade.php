@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <x-header title="Update Employee Records" subtitle="Employee's personal data sheet and portal leave-credits">
        <x-button-link 
            :href="route('hris.employee.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    
    <div class="row">
        <div class="col-12 col-md-3">
            @if($isExists)
                <x-hris-menu active="leave-credits" empno="{{ $employee_no }}" />
            @endif
        </div>

        <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
            <div class="accordion" id="leaveCreditsAccordion">
                <div class="legend mt-3 mb-4">
                    <h6 class="text-uppercase fw-bold">Legends</h6>
                    <ul class="list-inline mb-4">
                        <li class="list-inline-item"><span class="badge bg-primary">AM</span> Morning</li>
                        <li class="list-inline-item"><span class="badge bg-success">PM</span> Afternoon</li>
                        <li class="list-inline-item"><span class="badge bg-warning">WH</span> Whole Day</li>
                        <li class="list-inline-item"><span class="badge bg-danger">EQV</span> Equivalent</li>    
                    </ul>
                    <hr>
                </div>
                @php
                    $activeLeaveId = session('active_leave_id');
                    if (!$activeLeaveId && !empty($data)) {
                        $activeLeaveId = $data[0]['leave']->leave_id;
                    }
                @endphp
                @forelse($data as $leaveData)
                    @if($leaveData['hasAssignedDeduct'])
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-{{ $leaveData['leave']->leave_id }}">
                                <button class="accordion-button text-uppercase fw-bold 
                                    @if($leaveData['leave']->leave_id != $activeLeaveId) collapsed @endif" 
                                    type="button" 
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $leaveData['leave']->leave_id }}" 
                                    aria-expanded="{{ $leaveData['leave']->leave_id == $activeLeaveId ? 'true' : 'false' }}" 
                                    aria-controls="collapse-{{ $leaveData['leave']->leave_id }}">
                                    {{ $leaveData['leave']->name ?? 'Leave' }}
                                </button>
                            </h2>

                            <div id="collapse-{{ $leaveData['leave']->leave_id }}" 
                                class="accordion-collapse collapse @if($leaveData['leave']->leave_id == $activeLeaveId) show @endif" 
                                aria-labelledby="heading-{{ $leaveData['leave']->leave_id }}" 
                                data-bs-parent="#leaveCreditsAccordion">
                                <div class="accordion-body">
                                    @if($leaveData['hasDeduction'])
                                        <div class="mb-1 d-flex justify-content-end gap-3">
                                            <a href="{{ route('hris.employee.leave-credits.download', ['employee_no' => $employee_no, 'leave_id' => $leaveData['leave']->leave_id]) }}" class="btn btn-dark mt-3 mb-3 text-uppercase px-4">
                                                <i class="fa-solid fa-download me-1"></i> Download
                                            </a>
                                            <button class="btn btn-primary mt-3 mb-3 text-uppercase px-4 btn-add-credit"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#add-modal-credits"
                                                    data-leave-id="{{ $leaveData['leave']->leave_id }}"
                                                    data-previous="{{ $leaveData['latestCredits']['previous_balance'] ?? 0 }}"
                                                    data-as-of="{{ $leaveData['latestCredits']['current'] ?? now()->format('Y-m') }}">
                                                <i class="fa-solid fa-folder-plus me-1"></i> Update
                                            </button>
                                        </div>
                                    @endif

                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Previous</th>
                                                <th>Earned</th>
                                                <th>Deduction</th>
                                                <th>Balance</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($leaveData['credits'] as $credit)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($credit->as_of)->format('F, Y') }}</td>
                                                    <td>{{ $credit->previous }}</td>
                                                    <td>{{ $credit->earned }}</td>
                                                    <td>{{ $credit->deducted }}</td>
                                                    <td>{{ $credit->balance }}</td>
                                                    <td>
                                                        @php
                                                            // Split remarks by newline to count actual lines
                                                            $lines = $credit->remarks ? explode("\n", $credit->remarks) : [];
                                                            $rows = count($lines);               // number of existing lines
                                                            $rows = max(5, min($rows, 10));       // minimum 5 rows, maximum 10 rows
                                                        @endphp

                                                        <textarea 
                                                            class="form-control restricted" 
                                                            rows="{{ $rows }}" 
                                                            readonly
                                                        >{{ $credit->remarks }}</textarea>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">
                                                        No credits found
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    @if($leaveData['hasDeduction'])
                                        <div class="mt-4 mb-3">
                                            <span class="badge bg-primary text-uppercase"> as of {{ \Carbon\Carbon::now()->format('F') }}</span>
                                            <div>
                                                <strong class="text-uppercase">
                                                    <span class="text-decoration-underline">
                                                        Current Balance:  
                                                    </span>
                                                    <span class="bg-{{ ($leaveData['currentMonthBalance'] ?? 0) <= 0 ? 'danger' : 'primary' }} rounded-2 py-2 px-3 ms-2" style="font-size: 1.25rem;">
                                                        {{ $leaveData['currentMonthBalance'] ?? 0 }}
                                                    </span>
                                                </strong>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="alert alert-danger text-center text-uppercase fw-bold mt-5">
                        Oops! Sorry, Leave credits are only allowed for regular employee(s)
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Add / Update Modal --}}
<div class="modal fade" id="add-modal-credits" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="form" action="{{ route('hris.employee.leave-credits.save', ['employee_no' => $employee_no]) }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" name="leave_id" id="leave_id">
            <input type="hidden" name="action" id="action">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase">Leave Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 mb-3 col-md-3">
                            <label class="form-label">Previous Balance</label>
                            <input type="number" step="0.001" class="form-control restricted" name="previous_balance" id="previous_balance" readonly value="0.000">
                        </div>
                        <div class="col-12 mb-3 col-md-9">
                            <label class="form-label">Date</label>
                            <input type="month" class="form-control" name="as_of" id="as_of">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Earned</label>
                            <input type="number" step="0.001" class="form-control" name="earned" id="earned" value="0.000">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Deduction</label>
                            <input type="number" step="0.001" class="form-control" name="deduction" id="deduction" value="0.000">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Balance</label>
                            <input type="number" step="0.001" class="form-control restricted" name="balance" id="balance" readonly value="0.000">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="8"></textarea>
                            <div class="error-field"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" data-action="delete" class="btn-action btn btn-danger px-3 text-uppercase fw-bold">Delete</button>
                    <button type="submit" data-action="save" class="btn-action btn btn-primary px-3 text-uppercase fw-bold">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    const formActionURL = $('#form').attr('action');
    const fetchCreditsURL = @json(route('hris.employee.leave-credits.fetch', ['employee_no' => $employee_no]));
    put(formActionURL);

    function formatThreeDecimals(value) {
        const numericValue = parseFloat(value);
        return (Number.isFinite(numericValue) ? numericValue : 0).toFixed(3);
    }

    // Open modal: populate fields based on clicked leave type
    $('.btn-add-credit').on('click', function() {
        const leaveId = $(this).data('leave-id');
        const previous = $(this).data('previous') || 0;
        const asOf = $(this).data('as-of') || new Date().toISOString().slice(0,7);

        $('#leave_id').val(leaveId);
        $('#previous_balance').val(formatThreeDecimals(previous));
        $('#as_of').val(asOf);
        $('#earned').val('0.000');
        $('#deduction').val('0.000');
        $('#balance').val(formatThreeDecimals(previous));
        $('#remarks').val('');
        $('#action').val('');

        // Fetch existing credits for this leave and month
        fetchCredits(asOf, leaveId);
    });

    // Update balance automatically
    function updateBalance() {
        const previous = parseFloat($('#previous_balance').val()) || 0;
        const earned = parseFloat($('#earned').val()) || 0;
        const deduction = parseFloat($('#deduction').val()) || 0;
        $('#balance').val((previous + earned - deduction).toFixed(3));
    }

    $('#earned, #deduction').on('input', updateBalance);

    // Handle modal action buttons
    $('.btn-action').on('click', function() {
        $('#action').val($(this).data('action'));
    });

    // Fetch credits when "As Of" changes
    $('#as_of').on('change', function() {
        const as_of = $(this).val();
        const leaveId = $('#leave_id').val();
        fetchCredits(as_of, leaveId);
    });

    // Separate function to fetch credits
    function fetchCredits(as_of, leaveId) {
        if (!as_of || !leaveId) return;

        $.ajax({
            url: fetchCreditsURL,
            method: 'GET',
            data: { 
                as_of: as_of,
                leave_id: leaveId
            },
            success: function(response) {
                const res = response.data || {};
                $('#previous_balance').val(formatThreeDecimals(res.previous_balance || 0));
                $('#earned').val(formatThreeDecimals(res.current?.earned || 0));
                $('#deduction').val(formatThreeDecimals(res.current?.deducted || 0));
                $('#balance').val(formatThreeDecimals(res.current?.balance || 0));
                $('#remarks').val(res.current?.remarks || '');

                updateBalance();
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });
    }

    // Initialize balance on modal load
    updateBalance();
});
</script>
@endsection

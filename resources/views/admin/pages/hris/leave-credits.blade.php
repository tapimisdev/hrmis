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
            @if(!empty($data))
                <div class="mt-3 mb-4 d-flex justify-content-end">
                    <a href="{{ route('settings.leaves.index') }}" class="btn btn-primary text-uppercase fw-bold px-4">Add Leave Type</a>
                </div>
            @endif
            <div class="accordion" id="leaveCreditsAccordion">
                @php
                    $activeLeaveId = session('active_leave_id');
                @endphp
                @forelse($data as $leaveData)
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
                                <div class="mb-1 d-flex justify-content-end">
                                    <button class="btn btn-primary mt-3 mb-3 text-uppercase px-4 btn-add-credit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#add-modal-credits"
                                            data-leave-id="{{ $leaveData['leave']->leave_id }}"
                                            data-previous="{{ $leaveData['latestCredits']['previous_balance'] ?? 0 }}"
                                            data-as-of="{{ $leaveData['latestCredits']['current'] ?? now()->format('Y-m') }}">
                                        Add Credits
                                    </button>
                                </div>

                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>As Of</th>
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
                                                    <textarea class="form-control restricted" disabled>{{ $credit->remarks }}</textarea>
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

                                <div class="mt-4 mb-3">
                                    <strong>
                                        CURRRENT MONTH'S BALANCE:  
                                        <span class="bg-primary rounded-2 py-2 px-3 ms-2" style="font-size: 124x;">
                                            {{ $leaveData['currentMonthBalance'] ?? 0 }}
                                        </span>
                                    </strong> 
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-danger text-center text-uppercase fw-bold mt-5">
                        Oops! Sorry, Leave credits are only allowed for regular employee(s)
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Add Credits Modal --}}
<div class="modal fade" id="add-modal-credits" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="form" action="{{ route('hris.employee.leave-credits.save', ['employee_no' => $employee_no]) }}" method="POST">
            @csrf
            <input type="hidden" name="_method" id="method">
            <input type="hidden" name="leave_id" id="leave_id">
            <input type="hidden" name="action" id="action">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-uppercase">Add Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 mb-3 col-md-3">
                            <label class="form-label">Previous Balance</label>
                            <input type="number" step="0.01" class="form-control restricted" name="previous_balance" id="previous_balance" readonly value="0">
                        </div>
                        <div class="col-12 mb-3 col-md-9">
                            <label class="form-label">As Of</label>
                            <input type="month" class="form-control" name="as_of" id="as_of">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Earned</label>
                            <input type="number" step="0.01" class="form-control" name="earned" id="earned" value="0">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Deduction</label>
                            <input type="number" step="0.01" class="form-control" name="deduction" id="deduction" value="0">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3 col-md-4">
                            <label class="form-label">Balance</label>
                            <input type="number" step="0.01" class="form-control restricted" name="balance" id="balance" readonly value="0">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" name="remarks" id="remarks"></textarea>
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

    // Open modal: populate fields based on clicked leave type
    $('.btn-add-credit').on('click', function() {
        const leaveId = $(this).data('leave-id');
        const previous = $(this).data('previous') || 0;
        const asOf = $(this).data('as-of') || new Date().toISOString().slice(0,7);

        $('#leave_id').val(leaveId);
        $('#previous_balance').val(previous);
        $('#as_of').val(asOf);
        $('#earned').val(0);
        $('#deduction').val(0);
        $('#balance').val(previous);
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
        $('#balance').val((previous + earned - deduction).toFixed(2));
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
                $('#previous_balance').val(res.previous_balance || 0);
                $('#earned').val(res.current?.earned || 0);
                $('#deduction').val(res.current?.deducted || 0);
                $('#balance').val(res.current?.balance || 0);

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

@extends('employee.layout.app')

@section('content')
    <div class="container-fluid min-vh-100">
        
        <x-employee-navbar>
            <header-vue title="DOST TAPI"></header-vue>
        </x-employee-navbar>

        <x-header-employee title="Leave Credits" subtitle="View leave credits in this module" >
         
        </x-header-employee>

       @if(!empty($data))
            <div class="accordion" id="leaveCreditsAccordion">
                @php
                    $activeLeaveId = session('active_leave_id');
                @endphp
                @forelse($data as $leaveData)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $leaveData['leave']->leave_id }}">
                            <button class="accordion-button text-uppercase fw-bold @if($loop->first) @else collapsed @endif"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $leaveData['leave']->leave_id }}"
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-controls="collapse-{{ $leaveData['leave']->leave_id }}">
                                {{ $leaveData['leave']->name ?? 'Leave' }}
                            </button>
                        </h2>

                        <div id="collapse-{{ $leaveData['leave']->leave_id }}"
                            class="accordion-collapse collapse @if($loop->first) show @endif"
                            aria-labelledby="heading-{{ $leaveData['leave']->leave_id }}"
                            data-bs-parent="#leaveCreditsAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr class="text-uppercase">
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
                                                        @php
                                                            // Split remarks by newline to count actual lines
                                                            $lines = $credit->remarks ? explode("\n", $credit->remarks) : [];
                                                            $rows = count($lines);               // number of existing lines
                                                            $rows = max(2, min($rows, 8));       // minimum 2 rows, maximum 8 rows
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
                                </div>

                                <div class="mt-4 mb-3">
                                    <span class="badge bg-primary text-uppercase">{{ \Carbon\Carbon::now()->format('F') }}</span>
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
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-danger text-center text-uppercase fw-bold mt-5">
                        Oops! Sorry, Leave credits are only allowed for regular employee(s)
                    </div>
                @endforelse
            </div>
        @else
            <div class="alert alert-danger mt-3 text-center text-uppercase fw-bold">Oops! Sorry, Leave credits are allowed only for regular employee(s)</div>
        @endif
    </div>
@endsection
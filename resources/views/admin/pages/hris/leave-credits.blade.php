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
                <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-leave-credits" aria-expanded="true" aria-controls="flush-leave-credits">
                                Leave Credits
                            </button>
                        </h2>
                        <div id="flush-leave-credits" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                @if($leaves['status'] == 'eligible')
                                    @if(count($leaves['data']) > 0)
                                        {{-- === FORM 1: Combine Leave 1 & 2 === --}}
                                        @if(isset($leaves['data'][0]) && isset($leaves['data'][1]))
                                            <form id="form" action="{{ route('hris.employee.leave-credits', ['employee_no' => $employee_no]) }}" method="post">
                                                @method('PUT')
                                                @csrf
                                                <input type="hidden" name="forLeaveCard" id="forLeaveCard" value="true">
                                                <div class="table-responsive mt-3">
                                                    <table class="table table-bordered align-middle">
                                                        <thead class="table-light text-uppercase fw-bold">
                                                            <tr>
                                                                <th>Leave Name</th>
                                                                <th class="text-center" style="width: 200px;">Remaining Credits</th>
                                                                <th class="text-center" style="width: 200px;">Updated as of</th>
                                                                <th class="text-center" style="width: 150px;">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($leaves['data']->take(2) as $i => $leave)
                                                                <tr>
                                                                    <td class="fw-semibold text-uppercase mb-3">{{ $leave->name }}</td>
                                                                    <td>
                                                                        <input 
                                                                            type="text" 
                                                                            name="leave_id[{{ $leave->leave_id }}][value]" 
                                                                            id="leave_id.{{ $leave->leave_id }}.value"
                                                                            class="form-control text-center fw-semibold" 
                                                                            value="{{ $leave->amount }}"
                                                                        >
                                                                        <div class="error-field"></div>
                                                                    </td>

                                                                    @if($i === 0)
                                                                        <td rowspan="2" class="text-center align-middle mb-3">
                                                                            <input 
                                                                                type="date" 
                                                                                name="leave_id[{{ $leave->leave_id }}][as_of]" 
                                                                                id="leave_id.{{ $leave->leave_id }}.as_of"
                                                                                class="form-control text-center fw-semibold"
                                                                                value="{{ \Carbon\Carbon::parse($leave->effectivity_date)->format('Y-m-d') }}"
                                                                            >
                                                                            <div class="error-field"></div>
                                                                        </td>

                                                                        <td rowspan="2" class="text-center align-middle">
                                                                            @if(!$leave->hasLeaveCredit)
                                                                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                                            @else
                                                                                <a href="{{ route('hris.employee.leave-card', ['employee_no' => $employee_no]) }}" class="btn btn-sm btn-outline-secondary">Leave Card</a>
                                                                            @endif
                                                                        </td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </form>
                                        @endif

                                        {{-- === FORM 2: Remaining Leaves === --}}
                                        @if(count($leaves['data']) > 2)
                                            <form id="form" action="{{ route('hris.employee.leave-credits', ['employee_no' => $employee_no]) }}" method="post">
                                                @method('PUT')
                                                @csrf
                                                <input type="hidden" name="forLeaveCard" id="forLeaveCard" value="false">
                                                <div class="table-responsive mt-4">
                                                    <table class="table table-bordered align-middle">
                                                        <thead class="table-light text-uppercase fw-bold">
                                                            <tr>
                                                                <th>Leave Name</th>
                                                                <th class="text-center" style="width: 200px;">Remaining Credits</th>
                                                                <th class="text-center" style="width: 200px;">Updated as of</th>
                                                                <th class="text-center" style="width: 150px;">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($leaves['data']->skip(2) as $leave)
                                                                <tr>
                                                                    <td class="fw-semibold text-uppercase mb-3">{{ $leave->name }}</td>
                                                                    <td>
                                                                        <input 
                                                                            type="text" 
                                                                            name="leave_id[{{ $leave->leave_id }}][value]" 
                                                                            id="leave_id.{{ $leave->leave_id }}.value"
                                                                            class="form-control text-center fw-semibold" 
                                                                            value="{{ $leave->amount }}"
                                                                        >
                                                                        <div class="error-field"></div>
                                                                    </td>

                                                                    <td class="text-center align-middle mb-3">
                                                                        <input 
                                                                            type="date" 
                                                                            name="leave_id[{{ $leave->leave_id }}][as_of]" 
                                                                            id="leave_id.{{ $leave->leave_id }}.as_of"
                                                                            class="form-control text-center fw-semibold"
                                                                            value="{{ \Carbon\Carbon::parse($leave->effectivity_date)->format('Y-m-d') }}"
                                                                        >
                                                                        <div class="error-field"></div>
                                                                    </td>

                                                                    <td class="text-center align-middle">
                                                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </form>
                                        @endif
                                    @else
                                        <div class="alert alert-danger text-uppercase fw-bold mt-4">
                                            Oops! Sorry, No leave type(s) found. Please contact administrator or the HR personnel(s).
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-danger text-uppercase fw-bold mt-4">
                                        Oops! Sorry, Leave credits are only allowed for regular employee(s).
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        const url = $('#form').attr('action');
        put(url);
    });
</script>
@endsection

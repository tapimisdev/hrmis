@extends('admin.layouts.app')

@section('content')
    <div class="container pt-4 px-3">
        <x-header title="Update Employee Records" subtitle="Employee's personal data sheet and portal leave-credits">
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        <div class="row">
            <div class="col-12">
                <form id="form" action="{{ route('hris.employee.leave-credits', ['employee_no' => $employee_no]) }}" method="post">
                    @method('PUT') 
                    @csrf
                    @php
                        $latestYear = collect($data)->keys()->max();
                    @endphp

                    <div class="accordion" id="accordion-leave-card">
                        @foreach($data as $year => $months)
                            @php
                                $isLatest = $year == $latestYear;
                            @endphp

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ $year }}">
                                    <button class="accordion-button fw-bold text-uppercase {{ $isLatest ? '' : 'collapsed' }}" 
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse-{{ $year }}"
                                        aria-expanded="{{ $isLatest ? 'true' : 'false' }}"
                                        aria-controls="collapse-{{ $year }}">
                                        {{ $year }}
                                    </button>
                                </h2>

                                <div id="collapse-{{ $year }}" 
                                    class="accordion-collapse collapse {{ $isLatest ? 'show' : '' }}"
                                    aria-labelledby="heading-{{ $year }}"
                                    data-bs-parent="#accordion-leave-card">

                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead>
                                                    <tr>
                                                        <th rowspan="2">Period</th>
                                                        <th rowspan="2">Particulars</th>
                                                        <th colspan="4">Vacation Leave</th>
                                                        <th colspan="4">Sick Leave</th>
                                                        <th rowspan="2">Remarks</th>
                                                    </tr>
                                                    <tr>
                                                        <th>EARNED</th>
                                                        <th>AUT w/ pay</th>
                                                        <th>BAL.</th>
                                                        <th>AUT w/o pay</th>
                                                        <th>EARNED</th>
                                                        <th>AUT w/ pay</th>
                                                        <th>BAL.</th>
                                                        <th>AUT w/o pay</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($months as $month => $records)
                                                        @foreach($records as $record)
                                                            <tr>
                                                                <td class="text-uppercase">{{ $month }}</td>
                                                                <td>
                                                                    <textarea class="form-control" style="width: 400px; height: 100px;">{{ $record->particulars }}</textarea>
                                                                </td>

                                                                {{-- VACATION LEAVE --}}
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->vl_earned }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->vl_aut_w_pay }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->vl_bal }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->vl_aut_wo_pay }}"></td>

                                                                {{-- SICK LEAVE --}}
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->sl_earned }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->sl_aut_w_pay }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->sl_bal }}"></td>
                                                                <td><input type="text" class="form-control text-center" style="width: 100px;" value="{{ $record->sl_aut_wo_pay }}"></td>

                                                                <td>
                                                                    <textarea class="form-control" style="width: 200px; height: 80px;">{{ $record->remarks }}</textarea>
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
                        @endforeach
                    </div>

                </form>
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

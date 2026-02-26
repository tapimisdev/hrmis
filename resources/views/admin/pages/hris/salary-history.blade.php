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
                    <x-hris-menu active="salary-history" empno="{{ $employee_no }}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <div class="accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-salary-history" aria-expanded="true" aria-controls="flush-salary-history">
                                Salary History
                            </button>
                        </h2>
                        <div id="flush-salary-history" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Effectivity Date</th>
                                                <th>Salary Grade & Step</th>
                                                <th>Daily Rate</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data['history'] as $index => $item)
                                                <tr>
                                                    <td>
                                                        {{ \Carbon\Carbon::parse($item->effectivity_date)->format('F d, Y') }}
                                                        @if($loop->first)
                                                            <span class="text-primary fs-5 ms-1 pt-5">★</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ 'SG-' . $item->salary_grade . ' - ' . $item->step }}</td>
                                                    <td>{{ '₱' . $item->daily_rate }}</td>
                                                    <td>{{ '₱' . $item->amount }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

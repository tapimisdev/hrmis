@extends('employee.layout.app')

@section('content')
<div class="container-fluid min-vh-100">
    
    <x-employee-navbar>
        <header-vue :user-role="'employee'" :user-id='@json(Auth::id())'></header-vue>
    </x-employee-navbar>

    <x-header-employee title="Offset Credits" subtitle="View offset credits in this module" >
        
    </x-header-employee>

     <div class="accordion pb-5">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                    data-bs-target="#flush-offset-credits" aria-expanded="true" aria-controls="flush-offset-credits">
                    Offset Credits
                </button>
            </h2>
            <div id="flush-offset-credits" class="accordion-collapse collapse show">
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
                                @forelse($credits as $index => $credit)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($credit->as_of)->format('F, Y') }}</td>
                                        <td>{{ $credit->previous }}</td>
                                        <td>
                                            {{ $credit->earned }}
                                        </td>
                                        <td>
                                            {{ $credit->deducted }}
                                        </td>
                                        <td>
                                            {{ $credit->balance }}
                                        </td>
                                        <td>
                                            <textarea class="form-control restricted" value="{{ $credit->remarks }}" disabled></textarea>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

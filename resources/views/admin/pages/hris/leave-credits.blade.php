@extends('admin.layouts.app')

@section('content')
    <div class="container pt-4 px-3">
        <x-header title="Update Employee Leave Credits" subtitle="Employee's personal data sheet and portal leave-credits">
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
                <form id="form" action="{{ route('hris.employee.leave-credits', ['employee_no' => $employee_no]) }}" method="post">
                    @method('PUT') 
                    @csrf
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
                                    <div class="d-flex justify-content-end mb-4">
                                        <a href="" class="btn btn-outline-primary">Leave Card</a>
                                    </div>
                                    @if($leaves['status'] == 'eligible')
                                        <div class="row">
                                            @forelse($leaves['data'] as $leave)
                                                <div class="col-12 col-md-6 mb-3">
                                                    <div class="card">
                                                        <div class="card-header">
                                                            <div class="card-title m-0 text-uppercase fw-bold">{{$leave->name}}</div>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-12 col-md-12 mb-3">
                                                                    <label for="name" class="mb-1">Remaining Credits</label>
                                                                    <input type="text" name="leave_id[{{ $leave->leave_id }}]" class="form-control" value="{{$leave->amount}}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="alert alert-danger text-uppercase fw-bold mt-4">Oops! Sorry, No leave type(s) found. Please contact administrator or the HR personnel(s).</div>
                                            @endforelse
                                        </div>  

                                    @else
                                        <div class="alert alert-danger text-uppercase fw-bold mt-4">Oops! Sorry, Leave credits are only allowed for regular employee(s).</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($leaves['status'] == 'eligible')
                        <div class="bg-transparent border-0 d-flex justify-content-end mt-4">
                            <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                                Save <i class="fa-solid fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    @endif
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

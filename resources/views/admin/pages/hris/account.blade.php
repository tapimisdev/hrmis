@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Update Employee Account" subtitle="Update employee's portal account">
            <a href="{{ route('hris.employee.index') }}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>

        <x-hris-menu active="account" empno="{{ $employee_no }}" />

        <form id="form" action="{{ route('hris.employee.account', ['employee_no' => $employee_no]) }}" method="post">
            @method('PUT') 
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#flush-account" aria-expanded="true" aria-controls="flush-account">
                                    Account Setup
                                </button>
                            </h2>
                            <div id="flush-account" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">

                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="email">Email</label>
                                            <input type="email" id="email" name="email" 
                                                class="form-control restricted"
                                                value="{{ old('email', optional($data)->email) }}" readonly>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="password">Password</label>
                                            <input type="password" id="password" name="password" 
                                                class="form-control" placeholder="Leave blank to keep current">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="confirm_password">Confirm Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" 
                                                class="form-control" placeholder="Re-enter password">
                                            <div class="error-field"></div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                        Update <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
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

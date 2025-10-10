@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="Manage Employee Records" subtitle="Update employee's personal data sheet and portal account">
            <a href="{{ route('hris.employee.index') }}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i> Go Back
            </a>
        </x-header>
        <div class="row">
            <div class="col-12 col-md-3">
                @if($isExists)
                    <x-hris-menu active="account" empno="{{ $employee_no }}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <form id="form" action="{{ route('hris.employee.account', ['employee_no' => $employee_no]) }}" method="post">
                    @method('PUT') 
                    @csrf
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
                                                class="form-control" placeholder="New Password">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="confirm_password">Confirm Password</label>
                                            <input type="password" id="confirm_password" name="confirm_password" 
                                                class="form-control" placeholder="Confirm password">
                                            <div class="error-field"></div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-transparent border-0 d-flex justify-content-end mt-4">
                        <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                            Save <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
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

@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="employee personal data sheet and portal account" >
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
                    <x-hris-menu active="family" empno="{{$employee_no}}" />
                @endif
            </div>
            <div class="col-12 {{ $isExists ? 'col-md-9' : '' }}">
                <form id="form" action="{{route('hris.employee.family', ['employee_no' => $employee_no])}}" method="post">
                    @method('POST')
                    @csrf
                    <div class="accordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-family" aria-expanded="false" aria-controls="flush-family">
                                    Family Background
                                </button>
                            </h2>
                            <div id="flush-family" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="spouse_surname">Spouse's Surname</label>
                                            <input type="text" id="spouse_surname" name="spouse_surname" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_surname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="spouse_firstname">First Name</label>
                                            <input type="text" id="spouse_firstname" name="spouse_firstname" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_firstname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="spouse_middlename">Middle Name</label>
                                            <input type="text" id="spouse_middlename" name="spouse_middlename" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_middlename }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="spouse_suffix">Suffix</label>
                                            <select id="spouse_suffix" name="spouse_suffix" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="jr" {{ optional($data)->spouse_suffix == 'jr' ? 'selected' : '' }}>Jr</option>
                                                <option value="sr" {{ optional($data)->spouse_suffix == 'sr' ? 'selected' : '' }}>Sr</option>
                                                <option value="I" {{ optional($data)->spouse_suffix == 'I' ? 'selected' : '' }}>I</option>
                                                <option value="II" {{ optional($data)->spouse_suffix == 'II' ? 'selected' : '' }}>II</option>
                                                <option value="III" {{ optional($data)->spouse_suffix == 'III' ? 'selected' : '' }}>III</option>
                                                <option value="IV" {{ optional($data)->spouse_suffix == 'IV' ? 'selected' : '' }}>IV</option>
                                                <option value="V" {{ optional($data)->spouse_suffix == 'V' ? 'selected' : '' }}>V</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="spouse_occupation">Occupation</label>
                                            <input type="text" id="spouse_occupation" name="spouse_occupation" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_occupation }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="spouse_business_name_employer">Employer / Business Name</label>
                                            <input type="text" id="spouse_business_name_employer" name="spouse_business_name_employer" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_business_name_employer }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="spouse_business_address">Business Address</label>
                                            <input type="text" id="spouse_business_address" name="spouse_business_address" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_business_address }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="spouse_contact_no">Contact Number</label>
                                            <input type="text" id="spouse_contact_no" name="spouse_contact_no" class="form-control text-uppercase"
                                                value="{{ optional($data)->spouse_contact_no }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 mb-4"><hr></div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="father_surname">Father's Surname</label>
                                            <input type="text" id="father_surname" name="father_surname" class="form-control text-uppercase"
                                                value="{{ optional($data)->father_surname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="father_firstname">First Name</label>
                                            <input type="text" id="father_firstname" name="father_firstname" class="form-control text-uppercase"
                                                value="{{ optional($data)->father_firstname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="father_middlename">Middle Name</label>
                                            <input type="text" id="father_middlename" name="father_middlename" class="form-control text-uppercase"
                                                value="{{ optional($data)->father_middlename }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="father_suffix">Suffix</label>
                                            <select id="father_suffix" name="father_suffix" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="jr" {{ optional($data)->father_suffix == 'jr' ? 'selected' : '' }}>Jr</option>
                                                <option value="sr" {{ optional($data)->father_suffix == 'sr' ? 'selected' : '' }}>Sr</option>
                                                <option value="I" {{ optional($data)->father_suffix == 'I' ? 'selected' : '' }}>I</option>
                                                <option value="II" {{ optional($data)->father_suffix == 'II' ? 'selected' : '' }}>II</option>
                                                <option value="III" {{ optional($data)->father_suffix == 'III' ? 'selected' : '' }}>III</option>
                                                <option value="IV" {{ optional($data)->father_suffix == 'IV' ? 'selected' : '' }}>IV</option>
                                                <option value="V" {{ optional($data)->father_suffix == 'V' ? 'selected' : '' }}>V</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 mb-4"><hr></div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="mother_surname">Mother's Surname</label>
                                            <input type="text" id="mother_surname" name="mother_surname" class="form-control text-uppercase"
                                                value="{{ optional($data)->mother_surname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="mother_firstname">First Name</label>
                                            <input type="text" id="mother_firstname" name="mother_firstname" class="form-control text-uppercase"
                                                value="{{ optional($data)->mother_firstname }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="mother_middlename">Middle Name</label>
                                            <input type="text" id="mother_middlename" name="mother_middlename" class="form-control text-uppercase"
                                                value="{{ optional($data)->mother_middlename }}">
                                            <div class="error-field"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="transparent border-0 d-flex justify-content-end mt-4">
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
        post(url);

    });
</script>
@endsection



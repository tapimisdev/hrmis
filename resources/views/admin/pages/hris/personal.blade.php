@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        <x-header title="{{$isExists ? 'Update Employee Records' : 'Add New Employee'}}" subtitle="Create new employee's personal data sheet and portal account" >
            <x-button-link 
                :href="route('hris.employee.index')" 
                icon="fa-solid fa-arrow-left me-2" 
                text="Back" 
                variant="danger"
            />
        </x-header>
        @if($isExists)
            <x-hris-menu active="personal" empno="{{$employee_no}}" />
        @endif
        <form id="form" action="{{route('hris.employee.personal', ['employee_no' => $employee_no])}}" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="accordion" id="accordionTabPersonal">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-personal" aria-expanded="false" aria-controls="flush-personal">
                                    Personal Information
                                </button>
                            </h2>
                            <div id="flush-personal" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">
                                        @if(!empty($data))
                                            <div class="col-12 col-md-12 mb-3">
                                                <div class="row">
                                                    <div class="col-12 mb-3">
                                                        <img class="open-document" data-src="{{ $profile }}" id="profile-preview"
                                                            src="{{ $profile }}"
                                                            alt="Avatar of {{ $data->firstname . ' ' . $data->lastname }}"
                                                            style="width: 150px; padding: 2px; border: 1px solid #c3c3c3; object-fit: cover; cursor: pointer;">
                                                    </div> 

                                                    <div class="col-12 col-md-5 mb-3">
                                                        <label class="mb-2" for="profile">Profile Image</label>
                                                        <input type="file" name="profile" id="profile" class="form-control text-uppercase" accept="image/*">
                                                        <div class="error-field"></div>
                                                    </div>  
                                                </div>
                                            </div>  
                                        @endif
                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="lastname">Surname</label>
                                            <input type="text" name="lastname" id="lastname" class="form-control text-uppercase"
                                                value="{{ optional($data)->lastname }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="firstname">First Name</label>
                                            <input type="text" name="firstname" id="firstname" class="form-control text-uppercase"
                                                value="{{ optional($data)->firstname }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-3 mb-3">
                                            <label class="mb-2" for="middlename">Middle Name</label>
                                            <input type="text" name="middlename" id="middlename" class="form-control text-uppercase"
                                                value="{{ optional($data)->middlename }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-2 mb-3">
                                            <label class="mb-2" for="suffix">Suffix</label>
                                            <select name="suffix" id="suffix" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="jr" {{ optional($data)->suffix == 'jr' ? 'selected' : '' }}>Jr</option>
                                                <option value="sr" {{ optional($data)->suffix == 'sr' ? 'selected' : '' }}>Sr</option>
                                                <option value="I" {{ optional($data)->suffix == 'I' ? 'selected' : '' }}>I</option>
                                                <option value="II" {{ optional($data)->suffix == 'II' ? 'selected' : '' }}>II</option>
                                                <option value="III" {{ optional($data)->suffix == 'III' ? 'selected' : '' }}>III</option>
                                                <option value="IV" {{ optional($data)->suffix == 'IV' ? 'selected' : '' }}>IV</option>
                                                <option value="V" {{ optional($data)->suffix == 'V' ? 'selected' : '' }}>V</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="birthday">Date of Birth</label>
                                            <input type="date" name="birthday" id="birthday" class="form-control text-uppercase"
                                                value="{{ optional($data)->birthday }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="civil_status">Civil Status</label>
                                            <select name="civil_status" id="civil_status" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="single" {{ optional($data)->civil_status == 'single' ? 'selected' : '' }}>Single</option>
                                                <option value="married" {{ optional($data)->civil_status == 'married' ? 'selected' : '' }}>Married</option>
                                                <option value="divorced" {{ optional($data)->civil_status == 'divorced' ? 'selected' : '' }}>Divorced</option>
                                                <option value="separated" {{ optional($data)->civil_status == 'separated' ? 'selected' : '' }}>Separated</option>
                                                <option value="widowed" {{ optional($data)->civil_status == 'widowed' ? 'selected' : '' }}>Widowed</option>
                                                <option value="annulled" {{ optional($data)->civil_status == 'annulled' ? 'selected' : '' }}>Annulled</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="sex">Sex</label>
                                            <select name="sex" id="sex" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="male" {{ optional($data)->sex == 'male' ? 'selected' : '' }}>Male</option>
                                                <option value="female" {{ optional($data)->sex == 'female' ? 'selected' : '' }}>Female</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="citizenship">Citizenship</label>
                                            <select name="citizenship" id="citizenship" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="filipino" {{ optional($data)->citizenship == 'filipino' ? 'selected' : '' }}>Filipino</option>
                                                <option value="dual_citizenship" {{ optional($data)->citizenship == 'dual_citizenship' ? 'selected' : '' }}>Dual Citizenship</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>
                                        <div class="col-12 col-md-4 mb-3 citizen_content d-none">    
                                            <label class="mb-2" for="country">Country (Dual Citizenship)</label>
                                            <select name="country" id="country" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="{{ optional($data)->country }}" selected>{{ optional($data)->country }}</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="citizenship_type">Citizenship Type</label>
                                            <select name="citizenship_type" id="citizenship_type" class="form-select text-uppercase">
                                                <option value=""> - CHOOSE - </option>
                                                <option value="by_birth" {{ optional($data)->citizenship_type == 'by_birth' ? 'selected' : '' }}>By Birth</option>
                                                <option value="by_naturalization" {{ optional($data)->citizenship_type == 'by_naturalization' ? 'selected' : '' }}>By Naturalization</option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>
                                       <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="birth_certificate">Birth Certificate - (img/pdf)</label>
                                            <input type="file" name="birth_certificate" id="birth_certificate" class="form-control">
                                            <div class="error-field"></div>

                                            @if(!empty($data->birth_certificate))
                                                <div class="mt-2 d-flex justify-content-center text-uppercase">
                                                    <a href="{{ Storage::url('uploads/employees/' . $data->employee_no . '/birth_certificate/' . $data->birth_certificate) }}"
                                                        download="{{ $data->birth_certificate }}"
                                                        class="btn btn-sm btn-outline-primary fw-bold px-5">
                                                            Download Birth Certificate
                                                        </a>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="marriage_certificate">Marriage Certificate - (img/pdf)</label>
                                            <input type="file" name="marriage_certificate" id="marriage_certificate" class="form-control">
                                            <div class="error-field"></div>

                                            @if(!empty($data->marriage_certificate))
                                                <div class="mt-2 d-flex justify-content-center text-uppercase">
                                                    <a href="{{ Storage::url('uploads/employees/' . $data->employee_no . '/marriage_certificate/' . $data->marriage_certificate) }}" 
                                                    download="{{ $data->marriage_certificate }}"
                                                    class="btn btn-sm btn-outline-primary fw-bold px-5">
                                                        Download Marriage Certificate
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-address" aria-expanded="false" aria-controls="flush-address">
                                    Address
                                </button>
                            </h2>
                            <div id="flush-address" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="present_address">Residential Address</label>
                                            <input type="text" name="present_address" id="present_address" class="form-control text-uppercase"
                                                placeholder="House / Block / Lot / Street / Subdivision / Village / Barangay"
                                                value="{{ optional($data)->present_address }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="present_province">State / Province</label>
                                            <input type="text" name="present_province" id="present_province" class="form-control text-uppercase"
                                                value="{{ optional($data)->present_province }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="present_city">City / Municipality</label>
                                            <input type="text" name="present_city" id="present_city" class="form-control text-uppercase"
                                                value="{{ optional($data)->present_city }}">
                                            <div class="error-field"></div>
                                        </div> 
                                        <div class="col-12 mb-3"><hr></div> 
                                        <div class="col-12 col-md-12 mb-3">
                                            <label class="mb-2" for="permanent_address">Permanent Address</label>
                                            <input type="text" name="permanent_address" id="permanent_address" class="form-control text-uppercase"
                                                placeholder="House / Block / Lot / Street / Subdivision / Village / Barangay"
                                                value="{{ optional($data)->permanent_address }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="permanent_province">State / Province</label>
                                            <input type="text" name="permanent_province" id="permanent_province" class="form-control text-uppercase"
                                                value="{{ optional($data)->permanent_province }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="permanent_city">City / Municipality</label>
                                            <input type="text" name="permanent_city" id="permanent_city" class="form-control text-uppercase"
                                                value="{{ optional($data)->permanent_city }}">
                                            <div class="error-field"></div>
                                        </div>    
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-contact" aria-expanded="false" aria-controls="flush-contact">
                                    Contact Information
                                </button>
                            </h2>
                            <div id="flush-contact" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="mobile_number">Mobile No.</label>
                                            <input type="text" name="mobile_number" id="mobile_number" class="form-control text-uppercase" data-mask="mobile"
                                                value="{{ optional($data)->mobile_number }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="tel_no">Telephone No.</label>
                                            <input type="text" name="tel_no" id="tel_no" class="form-control text-uppercase"
                                                value="{{ optional($data)->tel_no }}">
                                            <div class="error-field"></div>
                                        </div>  
                                        <div class="col-12 col-md-4 mb-3">
                                            <label class="mb-2" for="email">Email</label>
                                            <input type="email" name="email" id="email" class="form-control"
                                                value="{{ optional($data)->email }}">
                                            <div class="error-field"></div>
                                        </div>  
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button text-uppercase fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#flush-appearance" aria-expanded="false" aria-controls="flush-appearance">
                                    Appearance
                                </button>
                            </h2>
                            <div id="flush-appearance" class="accordion-collapse collapse show">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="height">Height</label>
                                            <input type="text" name="height" id="height" class="form-control text-uppercase"
                                                value="{{ optional($data)->height }}">
                                            <div class="error-field"></div>
                                        </div>
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="weight">Weight</label>
                                            <input type="text" name="weight" id="weight" class="form-control text-uppercase"
                                                value="{{ optional($data)->weight }}">
                                            <div class="error-field"></div>
                                        </div>
                                        <div class="col-12 col-md-6 mb-3">
                                            <label class="mb-2" for="blood_type">Blood Type</label>
                                            <input type="text" name="blood_type" id="blood_type" class="form-control text-uppercase"
                                                value="{{ optional($data)->blood_type }}">
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
                        Save <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div id="galleryContainer"></div>
@endsection

@section('scripts')
<script>
    
    $(function() {


        $('#profile').on('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $('#profile-preview').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });

        $('#citizenship').on('change', function() {
            const val = $(this).val();
            if(val == 'dual_citizenship') {
                $('.citizen_content').removeClass('d-none');
                loadCountries()
                    .done(function(data) {
                        let $country = $('#country');
                        $country.html('<option value=""> - CHOOSE - </option>');

                        $.each(data, function(index, country) {
                            $country.append(
                                $('<option>', {
                                    value: country.name,
                                    text: country.name
                                })
                            );
                        });
                    })
                    .fail(function(xhr, status, error) {
                        console.error("Error loading countries:", error);
                    });

            } else {
                $('.citizen_content').addClass('d-none');
            }
        });

        const url = $('#form').attr('action');
        post(url);

    });
</script>
@endsection



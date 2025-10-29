@extends('admin.layouts.app')

@section('content')
    <div class="container pt-4 px-3">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Project" subtitle="Update this employee project">
                <x-button-link 
                    :href="route('projects.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @else
            <x-header title="Add New Project" subtitle="Create new project for employees" >
                <x-button-link 
                    :href="route('projects.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('projects.update', ['project' => $id]) : route('projects.store') }}" method="post">
            @if($isEdit)
                @method('PUT')
            @else
                @method('POST')
            @endif
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row my-3">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{$isEdit ? $data['name'] : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="employee_no">Choose Employees</label>
                            <select id="employee_nos" name="employee_nos[]" class="form-select select2" multiple="multiple" style="width: 75%">
                                <option value=""> - CHOOSE - </option>
                                @foreach ($employees as $divisionName => $units)
                                    <optgroup label="{{ $divisionName }}">
                                        @foreach ($units as $unitName => $unitEmployees)
                                            <optgroup label="&nbsp;&nbsp;{{ $unitName }}">
                                                @foreach ($unitEmployees as $employee)
                                                    <option value="{{ $employee->employee_no }}"
                                                        @if (!empty($data['employee_nos']) && in_array($employee->employee_no, $data['employee_nos'])) selected @endif>
                                                        {{ $employee->firstname . ' ' . $employee->lastname }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="start_date">Start Date <span class="text-danger">*</span></label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{$isEdit ? $data['start_date'] : ''}}">
                            <div class="error-field"></div>
                        </div>
                         <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="end_date">End Date <span class="text-danger">*</span></label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{$isEdit ? $data['end_date'] : ''}}">
                            <div class="error-field"></div>
                        </div>
                    </div> 
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');
        if(!isEdit) {
            post(url);
        } else {
            put(url);
        }
    });
</script>
@endsection



@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Import Payroll Registry" subtitle="Upload previous payroll registry">

        </x-header>

        <div class="container">
            <div class="card">
                <div class="card-body py-5 px-4">
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label for="type" class="mb-2">Type of Payroll <span class="text-danger">*</span></label>
                            <select name="type" id="type" class="form-select">
                                <option value=""> - CHOOSE - </option>
                                <option value="salary">Salary Pay</option>
                                <option value="hazard">Hazard Pay</option>
                                <option value="hazard">SLA Pay</option>
                                <option value="hazard">Pera & RATA Pay</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label for="type" class="mb-2">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                            Import
                        </button>
                    </div>
                </div>
            </div>
        </div>
    
    </div>
@endsection

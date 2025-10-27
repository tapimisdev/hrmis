@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container pt-4 px-3">

    <x-header title="Deduction" subtitle="Edit Deduction in this module">
        <x-button-link 
            :href="route('deductions.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <form id="form" action="{{route('deductions.update', ['deduction' => $deduction->id])}}" method="post">
        @csrf
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Deduction
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ $deduction->name ?? '' }}">
                        <div class="text-danger name_error error-field"></div>
                    </div>
                </div>
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="first_term">First Term Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="first_term" name="first_term" class="form-control" value="0"
                            value="{{ $deduction->first_term ?? '' }}">
                        <div class="text-danger first_term_error error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="second_term">Second Term Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="second_term" name="second_term" class="form-control" value="0"
                            value="{{ $deduction->second_term ?? '' }}">
                        <div class="text-danger second_term_error error-field"></div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top bg-transparent border-0 pt-4 d-flex justify-content-end">
                <button type="submit" id="update-button"
                        class="btn btn-primary px-5 py-3 text-uppercase fw-bold">
                    Update
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
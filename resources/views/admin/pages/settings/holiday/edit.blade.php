@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
 <div class="container pt-4 px-3">

    <x-header title="Holiday" subtitle="Manage Holiday in this module">
        <x-button-link 
            :href="route('holiday.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>
    <form id="form" action="{{ route('holiday.update', $holiday->id) }}" method="post">
        @csrf
        @method('PUT')
        <div class="card shadow p-3">
            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Holiday
                </h4>
            </div>
            <div class="card-body">
                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="name">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="{{ $holiday->name ?? '' }}">
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="date">Date <span class="text-danger">*</span></label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="{{ isset($holiday->date) ? \Carbon\Carbon::parse($holiday->date)->format('Y-m-d') : '' }}">
                         <div class="error-field"></div>
                    </div>
                </div>

                <div class="row my-3">
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="type">Type <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select">
                            <option value="regular" {{ (isset($holiday) && $holiday->type == 'regular') ? 'selected' : '' }}>Regular Holiday</option>
                            <option value="special_working" {{ (isset($holiday) && $holiday->type == 'special_working') ? 'selected' : '' }}>Special Working Day</option>
                            <option value="special_non_working" {{ (isset($holiday) && $holiday->type == 'special_non_working') ? 'selected' : '' }}>Special Non-working Day</option>
                            <option value="company" {{ (isset($holiday) && $holiday->type == 'company') ? 'selected' : '' }}>Company-declared Holiday</option>
                        </select>
                         <div class="error-field"></div>
                    </div>
                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2" for="is_repeating">Repeats Yearly?</label>
                        <select id="is_repeating" name="is_repeating" class="form-select">
                            <option value="0" {{ (isset($holiday) && !$holiday->is_repeating) ? 'selected' : '' }}>No</option>
                            <option value="1" {{ (isset($holiday) && $holiday->is_repeating) ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="error-field"></div>
                    </div>
                </div>

                {{-- New Rate Fields --}}
                <div class="row my-3">
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="no_work_rate">No Work Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="no_work_rate" name="no_work_rate" class="form-control" 
                               value="{{ $holiday->no_work_rate ?? '' }}" placeholder="e.g., 1.00">
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="work_rate">Work Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="work_rate" name="work_rate" class="form-control" 
                               value="{{ $holiday->work_rate ?? '' }}" placeholder="e.g., 1.30">
                        <div class="error-field"></div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <label class="mb-2" for="overtime_rate">Overtime Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="overtime_rate" name="overtime_rate" class="form-control" 
                               value="{{ $holiday->overtime_rate ?? '' }}" placeholder="e.g., 1.50">
                        <div class="error-field"></div>
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

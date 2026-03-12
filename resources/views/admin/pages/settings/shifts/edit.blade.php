@extends('admin.layouts.app')

@section('styles')
@endsection

@section('content')
<div class="container-fluid">

    <x-header title="Shift Schedule" subtitle="Manage shift schedule in this module">
        <x-button-link 
            :href="route('shift.index')" 
            icon="fa-solid fa-arrow-left me-2" 
            text="Back" 
            variant="danger"
        />
    </x-header>

    <form id="form" action="{{ route('shift.update', $shift->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card shadow p-3">

            <div class="card-header bg-transparent">
                <h4 class="m-0 mb-1 pt-3 text-uppercase fw-medium">
                    Edit Shift
                </h4>
            </div>

            <div class="card-body">

                {{-- Shift Basic Info --}}
                <div class="row my-3">

                    <div class="col-12 col-md-5 mb-3" id="shift_name_container">
                        <label class="mb-2">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ $shift->name }}">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-4 mb-3" id="flexible_container">
                        <label class="mb-2">Flexible</label>
                        <select name="is_flexible" id="is_flexible" class="form-select">
                            <option value="0" {{ !$shift->is_flexible ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $shift->is_flexible ? 'selected' : '' }}>Yes</option>
                        </select>
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-3 mb-3 {{ $shift->is_flexible ? 'd-none' : '' }}" id="grace_period_container">
                        <label class="mb-2">Grace Period</label>
                        <input type="number" min="0" name="grace_period" id="grace_period"
                               class="form-control"
                               value="{{ $shift->grace_period }}">
                        <div class="error-field"></div>
                    </div>

                </div>


                {{-- Time Settings --}}
                <div class="row my-3">

                    <div class="col-12 col-md-3 mb-3 {{ $shift->is_flexible ? '' : 'd-none' }}" id="earliest_time_container">
                        <label class="mb-2">Earliest Time</label>
                        <input type="time" name="earliest_time" id="earliest_time"
                               class="form-control"
                               value="{{ $shift->earliest_time ? \Carbon\Carbon::parse($shift->earliest_time)->format('H:i') : '' }}">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" id="start_time"
                               class="form-control"
                               value="{{ $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('H:i') : '' }}">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-3 mb-3 {{ $shift->is_flexible ? 'd-none' : '' }}" id="end_time_container">
                        <label class="mb-2">End Time</label>
                        <input type="time" name="end_time" id="end_time"
                               class="form-control"
                               value="{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('H:i') : '' }}">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2">Working Hours</label>
                        <input type="number" step="0.01" min="0"
                               name="working_hours"
                               class="form-control"
                               value="{{ $shift->working_hours }}">
                        <div class="error-field"></div>
                    </div>

                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-2">Minimum Overtime Hours</label>
                        <input type="number" step="0.01" min="0"
                               name="minimum_overtime_hours"
                               class="form-control"
                               value="{{ $shift->minimum_overtime_hours }}">
                        <div class="error-field"></div>
                    </div>

                </div>


                {{-- Break Times --}}
                <div class="row my-3">

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2">Break Out Time</label>
                        <input type="time" name="break_out_time"
                               class="form-control"
                               value="{{ $shift->break_out_time ? \Carbon\Carbon::parse($shift->break_out_time)->format('H:i') : '' }}">
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2">Break In Time</label>
                        <input type="time" name="break_in_time"
                               class="form-control"
                               value="{{ $shift->break_in_time ? \Carbon\Carbon::parse($shift->break_in_time)->format('H:i') : '' }}">
                    </div>

                </div>


                {{-- Other Settings --}}
                <div class="row my-3">

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2">Break Required</label>
                        <select name="is_break_required" class="form-select">
                            <option value="1" {{ $shift->is_break_required ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !$shift->is_break_required ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="mb-2">Night Shift</label>
                        <select name="is_night_shift" class="form-select">
                            <option value="0" {{ !$shift->is_night_shift ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $shift->is_night_shift ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                </div>

            </div>

            <div class="card-footer border-0 pt-4 d-flex justify-content-end">
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

$(function(){

    function toggleFlexible(){

        let flexible = $("#is_flexible").val();

        if(flexible == 1){

            $("#grace_period_container").addClass("d-none");
            $("#grace_period").val('');

            $("#shift_name_container")
                .removeClass("col-md-5")
                .addClass("col-md-6");

            $("#flexible_container")
                .removeClass("col-md-4")
                .addClass("col-md-6");

            $("#earliest_time_container").removeClass("d-none");
            $("#earliest_time").prop("disabled", false);

            $("#end_time_container").addClass("d-none");
            $("#end_time").prop("disabled", true).val('');

        } else {

            $("#grace_period_container").removeClass("d-none");

            $("#shift_name_container")
                .removeClass("col-md-6")
                .addClass("col-md-5");

            $("#flexible_container")
                .removeClass("col-md-6")
                .addClass("col-md-4");

            $("#earliest_time_container").addClass("d-none");
            $("#earliest_time").prop("disabled", true).val('');

            $("#end_time_container").removeClass("d-none");
            $("#end_time").prop("disabled", false);
        }

    }

    toggleFlexible();

    $("#is_flexible").on("change", toggleFlexible);

    const url = $('#form').attr('action');
    put(url);

});

</script>
@endsection
@extends('admin.layouts.app')

@section('content')
    <div class="container pt-4 px-3">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Tranche" subtitle="update this tranche">
                <x-button-link 
                    :href="route('services.suspensions.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @else
            <x-header title="Add New Suspension" subtitle="Create or add new suspensions" >
                <x-button-link 
                    :href="route('services.suspensions.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('services.suspensions.update', ['suspension' => $id]) : route('services.suspensions.store') }}" method="post">
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
                            <input type="name" id="name" name="name" class="form-control" value="{{$isEdit ? $data['name'] : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="6">{{$isEdit ? $data['description'] : ''}}</textarea>
                            <div class="error-field"></div>
                        </div>                        
                        <div class="col-12">
                            <label class="mb-2 d-block">Suspension Dates</label>
                            <div id="suspension-dates-wrapper">
                                @if ($isEdit && !empty($data['suspensions']))
                                    @foreach ($data['suspensions'] as $index => $suspension)
                                        <div class="suspension-date-item border rounded p-3 mb-4">
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="mb-2">Date <span class="text-danger">*</span></label>
                                                    <input
                                                        type="date"
                                                        name="suspensions[{{ $index }}][date]"
                                                        id="suspensions.{{ $index }}.date"
                                                        class="form-control"
                                                        value="{{ $suspension['date'] ?? '' }}"
                                                    >
                                                    <div class="error-field"></div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="mb-2">Type <span class="text-danger">*</span></label>
                                                    <select
                                                        name="suspensions[{{ $index }}][type]"
                                                        id="suspensions.{{ $index }}.type"
                                                        class="form-select type-select"
                                                    >
                                                        <option value="whole_day" {{ $suspension['type'] == 'whole_day' ? 'selected' : '' }}>Whole Day</option>
                                                        <option value="half_day" {{ $suspension['type'] == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                                    </select>
                                                    <div class="error-field"></div>
                                                </div>
                                                <div class="col-md-2 mb-3 from-time-container" style="{{ $suspension['type'] == 'half_day' ? '' : 'display:none;' }}">
                                                    <label class="mb-2">From Time</label>
                                                    <input
                                                        type="time"
                                                        name="suspensions[{{ $index }}][from_time]"
                                                        id="suspensions.{{ $index }}.from_time"
                                                        class="form-control from-time"
                                                        value="{{ \Carbon\Carbon::parse($suspension['from_time'])->format('H:i') }}"
                                                    >
                                                    <div class="error-field"></div>
                                                </div>
                                                <div class="col-md-2 mb-3 to-time-container" style="{{ $suspension['type'] == 'half_day' ? '' : 'display:none;' }}">
                                                    <label class="mb-2">To Time</label>
                                                    <input
                                                        type="time"
                                                        name="suspensions[{{ $index }}][to_time]"
                                                        id="suspensions.{{ $index }}.to_time"
                                                        class="form-control to-time"
                                                        value="{{ \Carbon\Carbon::parse($suspension['to_time'])->format('H:i') }}"
                                                    >
                                                    <div class="error-field"></div>
                                                </div>
                                                <div class="col-md-2 d-flex align-items-center">
                                                    <button type="button" class="btn btn-danger btn-remove-suspension w-100">Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    {{-- Blank input row when no suspensions --}}
                                    <div class="suspension-date-item border rounded p-3 mb-4">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="mb-2">Date <span class="text-danger">*</span></label>
                                                <input
                                                    type="date"
                                                    name="suspensions[0][date]"
                                                    id="suspensions.0.date"
                                                    class="form-control"
                                                >
                                                <div class="error-field"></div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="mb-2">Type <span class="text-danger">*</span></label>
                                                <select
                                                    name="suspensions[0][type]"
                                                    id="suspensions.0.type"
                                                    class="form-select type-select"
                                                >
                                                    <option value="whole_day">Whole Day</option>
                                                    <option value="half_day">Half Day</option>
                                                </select>
                                                <div class="error-field"></div>
                                            </div>
                                            <div class="col-md-2 mb-3 from-time-container" style="display: none;">
                                                <label class="mb-2">From Time</label>
                                                <input
                                                    type="time"
                                                    name="suspensions[0][from_time]"
                                                    id="suspensions.0.from_time"
                                                    class="form-control from-time"
                                                >
                                                <div class="error-field"></div>
                                            </div>
                                            <div class="col-md-2 mb-3 to-time-container" style="display: none;">
                                                <label class="mb-2">To Time</label>
                                                <input
                                                    type="time"
                                                    name="suspensions[0][to_time]"
                                                    id="suspensions.0.to_time"
                                                    class="form-control to-time"
                                                >
                                                <div class="error-field"></div>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <button type="button" class="btn btn-danger btn-remove-suspension w-100">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <button type="button" class="btn btn-primary px-4 text-uppercase mt-3" id="add-suspension-date">Add Date</button>
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
    $(function () {
        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');

        if (!isEdit) {
            post(url);
        } else {
            put(url);
        }

        let suspensionIndex = {{ $isEdit && !empty($data['suspensions']) ? count($data['suspensions']) : 1 }};

       $('#add-suspension-date').on('click', function () {
            let html = `
                <div class="suspension-date-item border rounded p-3 mb-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="mb-2">Date <span class="text-danger">*</span></label>
                            <input
                                type="date"
                                name="suspensions[${suspensionIndex}][date]"
                                id="suspensions.${suspensionIndex}.date"
                                class="form-control"
                            >
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="mb-2">Type <span class="text-danger">*</span></label>
                            <select
                                name="suspensions[${suspensionIndex}][type]"
                                id="suspensions.${suspensionIndex}.type"
                                class="form-select type-select"
                            >
                                <option value="whole_day">Whole Day</option>
                                <option value="half_day">Half Day</option>
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-2 mb-3 from-time-container" style="display: none;">
                            <label class="mb-2">From Time</label>
                            <input
                                type="time"
                                name="suspensions[${suspensionIndex}][from_time]"
                                id="suspensions.${suspensionIndex}.from_time"
                                class="form-control from-time"
                            >
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-2 mb-3 to-time-container" style="display: none;">
                            <label class="mb-2">To Time</label>
                            <input
                                type="time"
                                name="suspensions[${suspensionIndex}][to_time]"
                                id="suspensions.${suspensionIndex}.to_time"
                                class="form-control to-time"
                            >
                            <div class="error-field"></div>
                        </div>
                        <div class="col-md-2 d-flex align-items-center">
                            <button type="button" class="btn btn-danger btn-remove-suspension w-100">Remove</button>
                        </div>
                    </div>
                </div>
            `;

            $('#suspension-dates-wrapper').append(html);
            suspensionIndex++;
        });


        $(document).on('click', '.btn-remove-suspension', function () {
            $(this).closest('.suspension-date-item').remove();
        });

        $(document).on('change', '.type-select', function () {
            const parent = $(this).closest('.suspension-date-item');
            const value = $(this).val();
            if (value === 'half_day') {
                parent.find('.from-time-container').show();
                parent.find('.to-time-container').show();
            } else {
                parent.find('.from-time-container').hide().find('input').val('');
                parent.find('.to-time-container').hide().find('input').val('');
            }
        });

        $('.type-select').trigger('change');
    });
</script>
@endsection




@extends('admin.layouts.app')
@section('styles')
    <style>

        .banner-image {
            margin-top: 20px; 
            border-radius: 25px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .banner-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Events" subtitle="update this event or announcement">
                <x-button-link 
                    :href="route('services.events.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @else
            <x-header title="Add New Events" subtitle="create new event or positions" >
                <x-button-link 
                    :href="route('services.events.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('services.events.update', ['event' => $id]) : route('services.events.store') }}" method="post">
            @if($isEdit)
                @method('PUT')
            @else
                @method('POST')
            @endif
            @csrf
            <div class="card shadow p-3 mb-4">
                <div class="card-body">
                    <div class="row my-3">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" value="{{$isEdit ? $data['title'] : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="banner">Banner <span class="text-danger">*</span></label>
                            <input type="file" id="banner" name="banner" class="form-control">
                            <div class="error-field"></div>

                            @if($isEdit && $data['banner'])
                                <div class="banner-image">
                                    <img src="{{ Storage::url('events/attachments/' . $data['banner']) }}" alt="" srcset="">
                                </div>
                            @endif

                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="tags">Tags <span class="text-danger">*</span></label>
                            <select name="tags[]" id="tags" class="form-select select2" multiple>
                                <optgroup label="General Government">
                                    <option value="announcement" {{ in_array('announcement', $data['tags'] ?? []) ? 'selected' : '' }}>Announcement</option>
                                    <option value="advisory" {{ in_array('advisory', $data['tags'] ?? []) ? 'selected' : '' }}>Advisory</option>
                                    <option value="memo" {{ in_array('memo', $data['tags'] ?? []) ? 'selected' : '' }}>Memo</option>
                                    <option value="circular" {{ in_array('circular', $data['tags'] ?? []) ? 'selected' : '' }}>Circular</option>
                                    <option value="guidelines" {{ in_array('guidelines', $data['tags'] ?? []) ? 'selected' : '' }}>Guidelines</option>
                                    <option value="policy" {{ in_array('policy', $data['tags'] ?? []) ? 'selected' : '' }}>Policy</option>
                                    <option value="regulation" {{ in_array('regulation', $data['tags'] ?? []) ? 'selected' : '' }}>Regulation</option>
                                    <option value="public-service" {{ in_array('public-service', $data['tags'] ?? []) ? 'selected' : '' }}>Public Service</option>
                                    <option value="press-release" {{ in_array('press-release', $data['tags'] ?? []) ? 'selected' : '' }}>Press Release</option>
                                    <option value="update" {{ in_array('update', $data['tags'] ?? []) ? 'selected' : '' }}>Update</option>
                                </optgroup>

                                <optgroup label="Meetings & Engagements">
                                    <option value="meeting" {{ in_array('meeting', $data['tags'] ?? []) ? 'selected' : '' }}>Meeting</option>
                                    <option value="consultation" {{ in_array('consultation', $data['tags'] ?? []) ? 'selected' : '' }}>Consultation</option>
                                    <option value="public-hearing" {{ in_array('public-hearing', $data['tags'] ?? []) ? 'selected' : '' }}>Public Hearing</option>
                                    <option value="orientation" {{ in_array('orientation', $data['tags'] ?? []) ? 'selected' : '' }}>Orientation</option>
                                    <option value="training" {{ in_array('training', $data['tags'] ?? []) ? 'selected' : '' }}>Training</option>
                                    <option value="seminar" {{ in_array('seminar', $data['tags'] ?? []) ? 'selected' : '' }}>Seminar</option>
                                    <option value="workshop" {{ in_array('workshop', $data['tags'] ?? []) ? 'selected' : '' }}>Workshop</option>
                                </optgroup>

                                <optgroup label="Community & Civic Programs">
                                    <option value="health-camp" {{ in_array('health-camp', $data['tags'] ?? []) ? 'selected' : '' }}>Health Camp</option>
                                    <option value="vaccination" {{ in_array('vaccination', $data['tags'] ?? []) ? 'selected' : '' }}>Vaccination</option>
                                    <option value="cleanup-drive" {{ in_array('cleanup-drive', $data['tags'] ?? []) ? 'selected' : '' }}>Clean-up Drive</option>
                                    <option value="tree-planting" {{ in_array('tree-planting', $data['tags'] ?? []) ? 'selected' : '' }}>Tree Planting</option>
                                    <option value="blood-donation" {{ in_array('blood-donation', $data['tags'] ?? []) ? 'selected' : '' }}>Blood Donation</option>
                                    <option value="charity" {{ in_array('charity', $data['tags'] ?? []) ? 'selected' : '' }}>Charity</option>
                                    <option value="fundraiser" {{ in_array('fundraiser', $data['tags'] ?? []) ? 'selected' : '' }}>Fundraiser</option>
                                    <option value="donation-drive" {{ in_array('donation-drive', $data['tags'] ?? []) ? 'selected' : '' }}>Donation Drive</option>
                                    <option value="volunteering" {{ in_array('volunteering', $data['tags'] ?? []) ? 'selected' : '' }}>Volunteering</option>
                                    <option value="community-meeting" {{ in_array('community-meeting', $data['tags'] ?? []) ? 'selected' : '' }}>Community Meeting</option>
                                </optgroup>

                                <optgroup label="Emergency & Safety">
                                    <option value="emergency" {{ in_array('emergency', $data['tags'] ?? []) ? 'selected' : '' }}>Emergency</option>
                                    <option value="alert" {{ in_array('alert', $data['tags'] ?? []) ? 'selected' : '' }}>Alert</option>
                                    <option value="disaster-preparedness" {{ in_array('disaster-preparedness', $data['tags'] ?? []) ? 'selected' : '' }}>Disaster Preparedness</option>
                                    <option value="evacuation" {{ in_array('evacuation', $data['tags'] ?? []) ? 'selected' : '' }}>Evacuation</option>
                                    <option value="rescue-operation" {{ in_array('rescue-operation', $data['tags'] ?? []) ? 'selected' : '' }}>Rescue Operation</option>
                                    <option value="fire-drill" {{ in_array('fire-drill', $data['tags'] ?? []) ? 'selected' : '' }}>Fire Drill</option>
                                    <option value="safety-advisory" {{ in_array('safety-advisory', $data['tags'] ?? []) ? 'selected' : '' }}>Safety Advisory</option>
                                </optgroup>

                                <optgroup label="Holidays & Observances">
                                    <option value="independence-day" {{ in_array('independence-day', $data['tags'] ?? []) ? 'selected' : '' }}>Independence Day</option>
                                    <option value="national-heroes-day" {{ in_array('national-heroes-day', $data['tags'] ?? []) ? 'selected' : '' }}>National Heroes Day</option>
                                    <option value="labor-day" {{ in_array('labor-day', $data['tags'] ?? []) ? 'selected' : '' }}>Labor Day</option>
                                    <option value="election-day" {{ in_array('election-day', $data['tags'] ?? []) ? 'selected' : '' }}>Election Day</option>
                                    <option value="new-year" {{ in_array('new-year', $data['tags'] ?? []) ? 'selected' : '' }}>New Year</option>
                                    <option value="christmas" {{ in_array('christmas', $data['tags'] ?? []) ? 'selected' : '' }}>Christmas</option>
                                    <option value="all-saints" {{ in_array('all-saints', $data['tags'] ?? []) ? 'selected' : '' }}>All Saints Day</option>
                                    <option value="all-souls" {{ in_array('all-souls', $data['tags'] ?? []) ? 'selected' : '' }}>All Souls Day</option>
                                    <option value="ramadan" {{ in_array('ramadan', $data['tags'] ?? []) ? 'selected' : '' }}>Ramadan</option>
                                    <option value="thanksgiving" {{ in_array('thanksgiving', $data['tags'] ?? []) ? 'selected' : '' }}>Thanksgiving</option>
                                </optgroup>
                            </select>

                            <div class="error-field"></div>
                            </div>

                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="content">Content <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" cols="30" rows="10" class="form-control ckeditor">{{$isEdit ? $data['description'] : ''}}</textarea>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Posted On (optional)</label>
                            <input type="datetime-local" 
                                id="posted_on"  
                                name="posted_on" 
                                class="form-control" 
                                value="{{ $isEdit && $data['posted_on'] ? \Carbon\Carbon::parse($data['posted_on'])->format('Y-m-d\TH:i') : '' }}">
                            <div class="error-field"></div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Note: Leave empty if not a scheduled event</small>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="posted_by">Posted / Authored By <span class="text-danger">*</span></label>
                            <select name="posted_by[]" id="posted_by" class="form-select select2" multiple>
                                @php
                                    $postedByIds = !empty($data['posted_by']) ? array_column($data['posted_by'], 'id') : [];
                                @endphp
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" 
                                        {{ in_array($user->id, $postedByIds) ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>

                        <div class="col-12 col-md-12 mb-3">
                            <label class="form-label">Notifications & Options</label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" 
                                    name="push_notif" id="push_notif" checked
                                    {{ old('push_notif', $data['push_notif'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="push_notif">
                                    Push Notification
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" 
                                    name="email_notif" id="email_notif"
                                    {{ old('email_notif', $data['email_notif'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notif">
                                    Email Notification
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" 
                                    name="show_viewers" id="show_viewers"
                                    {{ old('show_viewers', $data['show_viewers'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_viewers">
                                    Show Viewers
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" 
                                    name="is_suspension" id="is_suspension"
                                    {{ old('is_suspension', $data['is_suspension'] ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_suspension">
                                    Mark as Suspension
                                </label>
                            </div>

                            <div class="error-field"></div>
                        </div>

                        {{-- Suspension Date Range --}}
                        <div id="suspension_date_range" style="display: {{ old('is_suspension', $data['is_suspension'] ?? 0) ? 'block' : 'none' }};">
                            <div id="suspension_rows">
                                @php
                                    $suspensions = old('suspensions', $data['suspensions'] ?? [ [] ]);
                                @endphp
                                @if ($isEdit && !empty($data['suspensions']))
                                    @foreach ($suspensions as $index => $suspension)
                                        <div class="suspension-row row g-2 mb-2">
                                            <div class="col-md-3 mb-3">
                                                <label for="suspensions.{{ $index }}.date" class="form-label">Date</label>
                                                <input type="date"
                                                    class="form-control"
                                                    name="suspensions[{{ $index }}][date]"
                                                    id="suspensions.{{ $index }}.date"
                                                    value="{{ $suspension['date'] ?? '' }}">
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="suspensions.{{ $index }}.type" class="form-label">Type</label>
                                                <select class="form-select suspend-select"
                                                        name="suspensions[{{ $index }}][type]"
                                                        id="suspensions.{{ $index }}.type">
                                                    <option value="whole_day"
                                                        {{ ($suspension['type'] ?? '') === 'whole_day' ? 'selected' : '' }}>
                                                        Whole Day
                                                    </option>
                                                    <option value="half_day"
                                                        {{ ($suspension['type'] ?? '') === 'half_day' ? 'selected' : '' }}>
                                                        Half Day
                                                    </option>
                                                </select>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="suspensions.{{ $index }}.from_time" class="form-label">From Time</label>
                                                <input type="time"
                                                    class="form-control"
                                                    name="suspensions[{{ $index }}][from_time]"
                                                    id="suspensions.{{ $index }}.from_time"
                                                    value="{{ \Carbon\Carbon::parse($suspension['from_time'])->format('H:i') }}"
                                                    {{ ( ($suspension['type'] ?? '') !== 'half_day' ) ? 'disabled' : '' }}>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-2 mb-3">
                                                <label for="suspensions.{{ $index }}.to_time" class="form-label">To Time</label>
                                                <input type="time"
                                                    class="form-control"
                                                    name="suspensions[{{ $index }}][to_time]"
                                                    id="suspensions.{{ $index }}.to_time"
                                                    value="{{ \Carbon\Carbon::parse($suspension['to_time'])->format('H:i') }}"
                                                    {{ ( ($suspension['type'] ?? '') !== 'half_day' ) ? 'disabled' : '' }}>
                                                <div class="error-field"></div>
                                            </div>

                                            <div class="col-md-2 d-flex align-items-center">
                                                <button type="button" class="btn btn-danger btn-remove-row">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="suspension-row row g-2 mb-2">
                                        <div class="col-md-3 mb-3">
                                            <label for="suspensions.0.date" class="form-label">Date</label>
                                            <input type="date"
                                                class="form-control"
                                                name="suspensions[0][date]"
                                                id="suspensions.0.date"
                                                value="{{ $suspension['date'] ?? '' }}">
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="suspensions.0.type" class="form-label">Type</label>
                                            <select class="form-select suspend-select"
                                                    name="suspensions[0][type]"
                                                    id="suspensions.0.type">
                                                <option value="whole_day"
                                                    {{ ($suspension['type'] ?? '') === 'whole_day' ? 'selected' : '' }}>
                                                    Whole Day
                                                </option>
                                                <option value="half_day"
                                                    {{ ($suspension['type'] ?? '') === 'half_day' ? 'selected' : '' }}>
                                                    Half Day
                                                </option>
                                            </select>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="suspensions.0.from_time" class="form-label">From Time</label>
                                            <input type="time"
                                                class="form-control"
                                                name="suspensions[0][from_time]"
                                                id="suspensions.0.from_time"
                                                value="{{ isset($suspension['from_time']) ? \Carbon\Carbon::parse($suspension['from_time'])->format('H:i') : '' }}"
                                                {{ (($suspension['type'] ?? '') !== 'half_day') ? 'disabled' : '' }}>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-md-2 mb-3">
                                            <label for="suspensions.0.to_time" class="form-label">To Time</label>
                                            <input type="time"
                                                class="form-control"
                                                name="suspensions[0][to_time]"
                                                id="suspensions.0.to_time"
                                                value="{{ isset($suspension['to_time']) ? \Carbon\Carbon::parse($suspension['to_time'])->format('H:i') : '' }}"
                                                {{ (($suspension['type'] ?? '') !== 'half_day') ? 'disabled' : '' }}>
                                            <div class="error-field"></div>
                                        </div>

                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-danger btn-remove-row">Delete</button>
                                        </div>
                                    </div>

                                @endif
                            </div>

                            <button type="button" class="btn btn-primary mt-2 text-uppercase px-4" id="add_suspension_row">
                                Add Suspension
                            </button>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" id="attachments-container">
                    @if(!empty($data['attachments']))
                        <label class="mb-2" for="name">Attachments (Optional)</label>
                        @foreach($data['attachments'] as $attachment)
                            <div class="col-12 mb-3 attachment-item">
                                <div class="d-flex gap-3 align-items-center">
                                    <input type="text" name="attachment_titles[]" class="form-control" 
                                        placeholder="Attachment Title" value="{{ $attachment['title'] }}">
                                    <input type="file" name="attachment_files[]" class="form-control">
                                    <div class="d-flex gap-2">
                                        <a href="{{ Storage::url('events/attachments/' . $attachment['filename']) }}" 
                                            download="{{ $attachment['filename'] }}" 
                                            target="_blank" 
                                            class="btn btn-primary text-decoration-none">
                                                <i class="fa-solid fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger remove-attachment" data-id="{{ $attachment["id"] }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12 mb-3 attachment-item">
                            <label class="mb-2" for="name">Attachments (Optional)</label>
                            <div class="d-flex gap-3 align-items-center">
                                <input type="text" name="attachment_titles[]" class="form-control" placeholder="Attachment Title">
                                <input type="file" name="attachment_files[]" class="form-control">
                                <button type="button" class="btn btn-danger remove-attachment" data-id="1">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" id="add-attachment" class="btn btn-dark mb-4 my-2">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <div class="card shadow p-2">
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
        let attachmentIndex = 0;

        $('#add-attachment').click(function () {
            attachmentIndex++;
            let html = `
            <div class="col-12 mb-3 attachment-item" id="attachment_${attachmentIndex}">
                <div class="d-flex gap-3 align-items-center">
                    <input type="text" name="attachment_titles[]" class="form-control" placeholder="Attachment Title">
                    <input type="file" name="attachment_files[]" class="form-control">
                    <button type="button" class="btn btn-danger remove-attachment" data-id="new-${attachmentIndex}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>`;
            $('#attachments-container').append(html);
        });

        $(document).on('click', '.remove-attachment', function () {
            let totalAttachments = $('.attachment-item').length;
            if (totalAttachments <= 1) {
                alert("At least one attachment is required.");
                return;
            }
            let id = $(this).data('id');
            if (id && !id.toString().startsWith('new-')) {
                $('#attachments-container').append(
                    `<input type="hidden" name="remove_attachments[]" value="${id}">`
                );
            }
            $(this).closest('.attachment-item').remove();
        });

        function toggleSuspensionRange() {
            if ($("#is_suspension").is(":checked")) {
                $("#suspension_date_range").slideDown();
            } else {
                $("#suspension_date_range").slideUp();
            }
        }
        $("#is_suspension").on("change", toggleSuspensionRange);
        toggleSuspensionRange();

        const isEdit = @json($isEdit);
        const url = $('#form').attr('action');
        if (!isEdit) {
            post(url);
            
        } else {
            put(url);
        }

        let suspensionIndex = {{ count(old('suspensions', $data['suspensions'] ?? [[]])) }};

        $('#add_suspension_row').click(function () {
            let row = `
            <div class="suspension-row row g-2 mb-2">
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="suspensions.${suspensionIndex}.date">Date</label>
                    <input type="date" class="form-control"
                        name="suspensions[${suspensionIndex}][date]"
                        id="suspensions.${suspensionIndex}.date">
                    <div class="error-field"></div>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label" for="suspensions.${suspensionIndex}.type">Type</label>
                    <select class="form-select suspend-select"
                            name="suspensions[${suspensionIndex}][type]"
                            id="suspensions.${suspensionIndex}.type">
                        <option value="whole_day">Whole Day</option>
                        <option value="half_day">Half Day</option>
                    </select>
                    <div class="error-field"></div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label" for="suspensions.${suspensionIndex}.from_time">From Time</label>
                    <input type="time" class="form-control"
                        name="suspensions[${suspensionIndex}][from_time]"
                        id="suspensions.${suspensionIndex}.from_time" disabled>
                    <div class="error-field"></div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label" for="suspensions.${suspensionIndex}.to_time">To Time</label>
                    <input type="time" class="form-control"
                        name="suspensions[${suspensionIndex}][to_time]"
                        id="suspensions.${suspensionIndex}.to_time" disabled>
                    <div class="error-field"></div>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <button type="button" class="btn btn-danger btn-remove-row">Delete</button>
                </div>
            </div>`;
            $('#suspension_rows').append(row);
            bindSuspendSelectChange(suspensionIndex);
            suspensionIndex++;
        });

        $(document).on('click', '.btn-remove-row', function () {
            $(this).closest('.suspension-row').remove();
        });

        $('.suspend-select').each(function () {
            let id = $(this).attr('id');
            let parts = id.split('.');
            if (parts.length >= 3) {
                let index = parts[1];
                bindSuspendSelectChange(index);
            }
        });

        function bindSuspendSelectChange(index) {
            $(`#suspensions\\.${index}\\.type`).on('change', function () {
                const val = $(this).val();
                const fromInput = $(`#suspensions\\.${index}\\.from_time`);
                const toInput   = $(`#suspensions\\.${index}\\.to_time`);
                if (val === 'whole_day') {
                    fromInput.val('').prop('disabled', true);
                    toInput.val('').prop('disabled', true);
                } else if (val === 'half_day') {
                    fromInput.prop('disabled', false);
                    toInput.prop('disabled', false);
                }
            });
        }
    });
</script>
@endsection







@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Events" subtitle="update this event or announcement">
                <a href="{{route('services.events.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-arrow-left me-2"></i>Go Back
                </a>
            </x-header>
        @else
            <x-header title="Add New Events" subtitle="create new event or positions" >
                <a href="{{route('services.events.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    <i class="fa-solid fa-arrow-left me-2"></i>Go Back
                </a>
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('services.events.update', ['id' => $id]) : route('services.events.store') }}" method="post">
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
                            <input type="text" id="title" name="title" class="form-control" value="{{$isEdit ? $data->title : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="content">Content <span class="text-danger">*</span></label>
                            <textarea name="content" id="content" cols="30" rows="10" class="form-control ckeditor">{{$isEdit ? $data->description : ''}}</textarea>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Posted On (optional)</label>
                            <input type="datetime-local" id="posted_on"  name="posted_on" class="form-control" value="{{$isEdit ? $data->posted_on : ''}}">
                            <div class="error-field"></div>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">Note: Leave empty if not a scheduled event</small>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Posted / Authored By <span class="text-danger">*</span></label>
                            <select name="posted_by" id="posted_by" class="form-select select2" multiple>
                                <option value=""> - CHOOSE - </option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ !$isEdit && $posted_by == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-12 mb-3">
                            <label class="form-label">Notifications & Options</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="push_notification" id="push_notification" {{ old('push_notification', $data->push_notification ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="push_notification">
                                    Push Notification
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="email_notif" id="email_notif" {{ old('email_notif', $data->email_notif ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notif">
                                    Email Notification
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="show_viewers" id="show_viewers" {{ old('show_viewers', $data->show_viewers ?? 0) ? 'checked' : '' }}>
                                <label class="form-check-label" for="show_viewers">
                                    Show Viewers
                                </label>
                            </div>
                            <div class="error-field"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow p-3 mb-4">
                <div class="card-body">
                    <div class="row" id="attachments-container">
                        <div class="col-12 mb-3 attachment-item">
                            <label class="mb-2" for="name">Attachments (Optional)</label>
                            <div class="d-flex gap-3 align-items-center">
                                <input type="text" name="attachment_titles[]" class="form-control" placeholder="Attachment Title">
                                <input type="file" name="attachment_files[]" class="form-control">
                                <button type="button" class="btn btn-danger remove-attachment">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-attachment" class="btn btn-dark my-2">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
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
    $(function() {

        $('#add-attachment').click(function() {
            let html = `
            <div class="col-12 mb-3 attachment-item">
                <div class="d-flex gap-3 align-items-center">
                    <input type="text" name="attachment_titles[]" class="form-control" placeholder="Attachment Title">
                    <input type="file" name="attachment_files[]" class="form-control">
                    <button type="button" class="btn btn-danger remove-attachment">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>`;
            $('#attachments-container').append(html);
        });

        $(document).on('click', '.remove-attachment', function() {
            if ($('#attachments-container .attachment-item').length > 1) {
                $(this).closest('.attachment-item').remove();
            } else {
                alert('At least one attachment is required.');
            }
        });

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



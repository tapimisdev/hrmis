@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        {{-- Header --}}
        @if($isEdit ?? false)
            <x-header title="Update Approver" subtitle="Update approver/s">
                <a href="{{ route('settings.approvers.index') }}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                    Go Back
                </a>
            </x-header>
        @else
            <x-header title="Add New Approver" subtitle="Create new approver/s">
                <a href="{{ route('settings.approvers.index') }}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                    Go Back
                </a>
            </x-header>
        @endif

        {{-- Form --}}
        <form id="form"
              action="{{ ($isEdit ?? false)
                        ? route('settings.approvers.update', ['approver' => $id])
                        : route('settings.approvers.store') }}"
              method="post">
            @csrf
            @if($isEdit ?? false)
                @method('PUT')
            @else
                @method('POST')
            @endif

            <div class="row">
                <input type="hidden" name="text" id="text" class="level_approver" value="1">

                {{-- Basic Info --}}
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                {{-- Name --}}
                                <div class="col-12 mb-3">
                                    <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="form-control"
                                           value="{{ old('name', $data['name'] ?? '') }}"
                                           placeholder="Enter Name">
                                    <div class="error-field"></div>
                                </div>

                                {{-- Description --}}
                                <div class="col-12 mb-3">
                                    <label class="mb-2" for="description">Description</label>
                                    <textarea name="description" id="description" cols="30" rows="5"
                                              class="form-control"
                                              placeholder="Write Something...">{{ old('description', $data['description'] ?? '') }}</textarea>
                                    <div class="error-field"></div>
                                </div>

                                {{-- Type --}}
                                <div class="col-12 mb-3">
                                    <label class="mb-2" for="type">Application Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type" class="form-select">
                                        <option value=""> - CHOOSE TYPE - </option>
                                        @foreach (['leave', 'pass_slip', 'overtime'] as $type)
                                            <option value="{{ $type }}"
                                                {{ old('type', $data['type'] ?? '') == $type ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-5 mb-3">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <div class="row my-3">
                                <div class="col-12 mb-3">
                                    <label class="mb-2" for="division">Division <span class="text-danger">*</span></label>
                                    <select name="division_id" id="division_id" class="form-select">
                                        <option value=""> - CHOOSE DIVISION - </option>
                                        @foreach ($divisions ?? [] as $division)
                                            <option value="{{ $division->id }}"
                                                {{ old('division_id', $data['division_id'][0] ?? '') == $division->id ? 'selected' : '' }}>
                                                ({{ $division->code }}) {{ $division->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="mb-2" for="unit">Unit <span class="text-danger">*</span></label>
                                    <select name="unit_id[]" id="unit_id" class="form-select select2" multiple>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}"
                                                @if(($isEdit ?? false) && in_array($unit->id, (array) ($data['unit_id'] ?? []))) selected @endif>
                                                {{ $unit->name ?? $unit->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="error-field"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               <div class="col-12 col-md-7 mb-3">
                    <div class="row px-2" id="input-container">
                        @if($isEdit)
                            @foreach($data['users'] as $index => $selectedUsers)
                                @php
                                    $selectedIds = collect($selectedUsers)->pluck('id')->toArray();
                                @endphp

                                <div class="col-12 mb-2 approver-item" id="approver_item_{{ $index }}">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="flex-grow-1">
                                            <label class="form-label">
                                                {{ $loop->iteration }}{{ $loop->iteration == 1 ? 'st' : ($loop->iteration == 2 ? 'nd' : ($loop->iteration == 3 ? 'rd' : 'th')) }} Approval
                                            </label>
                                            <select name="approvers[{{ $index }}][]" class="form-select select2" multiple>
                                                @foreach($users as $u)
                                                    @php
                                                        $displayName = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: $u->name;
                                                    @endphp
                                                    <option value="{{ $u->id }}" 
                                                        {{ in_array($u->id, $selectedIds) ? 'selected' : '' }}>
                                                        {{ $displayName }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if($index > 1)
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm remove-approver" 
                                                    data-id="{{ $index }}">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-12 mb-2 approver-item" id="approver_item_0">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="flex-grow-1">
                                        <label class="form-label">
                                            1st Approval
                                        </label>
                                        <select name="approvers[1][]" class="form-select select2" multiple>
                                            @foreach($users as $u)
                                                @php
                                                    $displayName = trim(($u->firstname ?? '') . ' ' . ($u->lastname ?? '')) ?: $u->name;
                                                @endphp
                                                <option value="{{ $u->id }}">
                                                    {{ $displayName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" id="add-input" class="btn btn-outline-primary px-4 py-2 mt-2 text-uppercase">
                            Add Approver
                        </button>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="d-flex justify-content-end mt-5 pt-5">
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Save</button>
            </div>
        </form>
    </div>
@endsection


@section('scripts')
<script>
$(function() {

    let users = @json($users ?? []); // preload users from controller
    let approverCount = 1;
    const urlEmployees = @json(route('hris.employee.information'));

    // Fetch units when division changes
    $('#division_id').on('change', function() {
        const divisionId = $(this).val();
        $.getJSON(urlEmployees, { division_id: divisionId }, function(response) {
            const units = response?.data || [];
            $('#unit_id').html('');
            units.forEach(unit => {
                $('#unit_id').append(
                    `<option value="${unit.id}">${unit.name.toUpperCase()}</option>`
                );
            });
        });
    });

    function buildOptions() {
        return users.map(u => `<option value="${u.id}">${u.name}</option>`).join('');
    }

    function addApprover() {
        approverCount++;
        $('.level_approver').val(approverCount);
        const newSelect = $(`
            <div class="col-12 mb-2 approver-item" id="approver_item_${approverCount}">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <label class="form-label">${approverCount}${ordinalSuffix(approverCount)} Approver</label>
                        <select name="approvers[${approverCount}][]" class="form-select select2" multiple>
                            ${buildOptions()}
                        </select>
                    </div>
                    ${approverCount > 1 ? `<button type="button" class="btn btn-danger btn-sm remove-approver" data-id="${approverCount}">✕</button>` : ''}
                </div>
            </div>
        `);
        $('#input-container').append(newSelect);
        newSelect.find('.select2').select2({ width: '100%' });
    }

    function ordinalSuffix(i) {
        const j = i % 10, k = i % 100;
        if (j === 1 && k !== 11) return "st";
        if (j === 2 && k !== 12) return "nd";
        if (j === 3 && k !== 13) return "rd";
        return "th";
    }

    // Add approver button
    $('#add-input').on('click', addApprover);

    // Remove approver
    $(document).on('click', '.remove-approver', function() {
        const id = $(this).data('id');
        $(`#approver_item_${id}`).remove();
        approverCount--;
        $('.level_approver').val(approverCount);
    });

    // Auto-init edit mode approvers with Select2
    if(@json($isEdit)) {
        approverCount = $('.approver-item').length;
        $('.select2').select2({ width: '100%' });
    }

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

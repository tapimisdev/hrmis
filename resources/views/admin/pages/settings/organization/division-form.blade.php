@extends('admin.layouts.app')

@section('styles')
<style>
    #division_manager_id + .select2 .select2-selection--single {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        min-height: 38px;
    }

    #division_manager_id + .select2 .select2-selection__rendered {
        color: var(--bs-body-color);
        line-height: 36px;
    }

    #division_manager_id + .select2 .select2-selection__placeholder {
        color: var(--bs-secondary-color);
    }

    #division_manager_id + .select2 .select2-selection__arrow {
        height: 36px;
    }

    #division_manager_id + .select2 .select2-selection__arrow b {
        border-top-color: var(--bs-secondary-color);
    }
</style>
@endsection

@section('content')
    <div class="container-fluid">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Division" subtitle="Update this division in your organization">
                <x-button-link 
                    :href="route('organization.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @else
            <x-header title="Add New Division" subtitle="Create new division in your organization">
                <x-button-link 
                    :href="route('organization.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('organization.update', ['organization' => $id]) : route('organization.store') }}" method="post">
            @if($isEdit)
                @method('PUT')
            @else
                @method('POST')
            @endif
            @csrf
            <input type="hidden" name="type" value="{{$type}}">
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row my-3">
                        <div class="col-12 col-md-4 mb-3">
                            <label class="mb-2" for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code" class="form-control" value="{{$isEdit ? $data->code : ''}}">
                            <div class="error-field"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{$isEdit ? $data->name : ''}}">
                            <div class="error-field"></div>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="division_manager_id">Division Manager/Chief</label>
                            <select id="division_manager_id" name="division_manager_id" class="form-select select2" data-placeholder="- CHOOSE DIVISION MANAGER/CHIEF -">
                                <option value=""> - CHOOSE DIVISION MANAGER/CHIEF - </option>
                                @foreach($divisionManagers ?? [] as $manager)
                                    <option value="{{ $manager->id }}"
                                        {{ (string) old('division_manager_id', $isEdit ? ($data->division_manager_id ?? '') : '') === (string) $manager->id ? 'selected' : '' }}>
                                        {{ $manager->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Description</label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control p-3" placeholder="Type something...">{{$isEdit ? $data->description : ''}}</textarea>
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

        $('.select2').select2({
            width: '100%'
        });

        if(!isEdit) {
            post(url);
        } else {
            put(url);
        }
    });
</script>
@endsection

@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Unit" subtitle="Update this unit in your organization">
                <x-button-link 
                    :href="route('organization.index')" 
                    icon="fa-solid fa-arrow-left me-2" 
                    text="Back" 
                    variant="danger"
                />
            </x-header>
        @else
            <x-header title="Add New Unit" subtitle="Create new unit in your organization">
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
                        <div class="col-12 col-md-6">
                            <label class="mb-2" for="division">Division <span class="text-danger">*</span></label>
                            <select name="division_id" id="division_id" class="form-select">
                                <option value=""> - CHOOSE DIVISION - </option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->id }}" {{ $isEdit && $data->division_id == $division->id ? 'selected' : '' }}>
                                        {{ strtoupper($division->code . ' - ' . $division->name) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="error-field"></div>
                        </div>
                    </div>
                    <div class="row my-3">
                        <div class="col-12 col-md-6">
                            <label class="mb-2" for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code" class="form-control" value="{{$isEdit ? $data->code : ''}}">
                            <div class="error-field"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{$isEdit ? $data->name : ''}}">
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
        if(!isEdit) {
            post(url);
        } else {
            put(url);
        }
    });
</script>
@endsection



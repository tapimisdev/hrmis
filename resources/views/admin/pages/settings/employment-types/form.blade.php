@extends('admin.layouts.app')

@section('content')
    <div class="container p-4 pb-5">
        @if(isset($isEdit) && $isEdit == true)
            <x-header title="Update Employment Type" subtitle="update this employment type" >
                <a href="{{route('employment-types.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    Go Back
                </a>
            </x-header>
        @else
            <x-header title="Add New Employment Type" subtitle="Create new employment type" >
                <a href="{{route('employment-types.index')}}" class="btn btn-outline-danger py-3 px-4 text-uppercase fw-medium">
                    Go Back
                </a>
            </x-header>
        @endif
        <form id="form" action="{{ $isEdit ? route('employment-types.update', ['employment_type' => $id]) : route('employment-types.store') }}" method="post">
            @if($isEdit)
                @method('PUT')
            @else
                @method('POST')
            @endif
            @csrf
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



@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4">
        <x-header title="Add New Employment Type" subtitle="" >
            <a href="{{route('employment-types.index')}}" class="btn btn-primary py-3 px-4 text-uppercase fw-medium">
                Go Back
            </a>
        </x-header>
        <form id="form" method="post">
            @method('POST')
            @csrf
            <div class="card shadow p-3">
                <div class="card-body">
                    <div class="row my-3">
                        <div class="col-12 col-md-3 mb-3">
                            <label class="mb-2" for="code">Code <span class="text-danger">*</span></label>
                            <input type="text" id="code" name="code" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label class="mb-2" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-12 mb-3">
                            <label class="mb-2" for="name">Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control p-3" placeholder="Type something..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Save</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    $(function() {
        $('#form').on('submit', function(e) {
            e.preventDefault();
            
            let formData = $(this).serialize();

            $.ajax({
                url: '{{ route('employment-types.store') }}',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        window.location.href = '{{ route('employment-types.index') }}';
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while saving the data.');
                }
            });
        });
    });
</script>
@endsection



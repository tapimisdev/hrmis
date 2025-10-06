@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container p-4 pb-5">
        <dtr-view-vue :employee_id="{{ json_encode($id) }}" ></dtr-view-vue>
    </div>
@endsection

@section('scripts')
<script>
</script>
@endsection



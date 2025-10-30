@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <div class="container-fluid">
        <dtr-view-vue :employee_id="{{ json_encode($employee_no) }}" ></dtr-view-vue>
    </div>
@endsection

@section('scripts')
<script>
</script>
@endsection



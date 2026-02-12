@extends('admin.layouts.app')

@section('styles')

@endsection

@section('content')
    <!-- <div class="container-fluid">
        <dtr-view-vue :employee_id="{{ json_encode($employee_no) }}" ></dtr-view-vue>
    </div> -->

     <dtr-view-vue 
        :employee_no="{{ json_encode($employee_no) }}"
        :employee_id="{{ json_encode($employee_id) }}">
    </dtr-view-vue>
@endsection

@section('scripts')
<script>
</script>
@endsection



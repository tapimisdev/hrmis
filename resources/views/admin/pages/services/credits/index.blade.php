@extends('admin.layouts.app')

@section('styles')
<style>
    /* Ensure borders appear even if component overrides */
    #myTable {
        border-collapse: collapse;
        width: fit-content;
    }

    #myTable th,
    #myTable td {
        padding: 10px 50px 10px 50px;
        border: 1px solid #dee2e6;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <x-header title="Credits" subtitle="Manage credits in this module">
    </x-header>

    <x-table id="myTable" class="table table-bordered">
        <thead>
            <tr>
                <th>Employee No</th>
                <th>Name</th>
                <th>VL</th>
                <th>SL</th>
                <th>WL</th>
                <th>Offset</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $empNo => $employee)
                <tr>
                    <td>{{ $empNo }}</td>
                    <td>{{$employee['firstname'] . ' ' . $employee['lastname']}}</td>
                    <td>{{ $employee['vl'] ?? 0 }}</td>
                    <td>{{ $employee['sl'] ?? 0 }}</td>
                    <td>{{ $employee['wl'] ?? 0 }}</td>
                    <td>{{ $employee['offset'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-table>
</div>
@endsection
@extends('admin.layouts.app')

@section('styles')
<style>
    .row .card-body {
        height: 400px;        
        overflow-y: auto;   
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <x-header title="Timelogs Monitoring" subtitle="View monitoring for employee's timelogs in this module">
    </x-header>
    <div class="my-4">
        <div class="card">
            <div class="card-body">
                <label for="date" class="mb-2">Choose Date</label>
                <input 
                    type="date" 
                    name="date" 
                    id="date" 
                    class="form-control" 
                    value="{{ request('date', $date) }}" 
                    onchange="window.location.href='?date='+this.value"
                >
            </div>
        </div>
    </div>
    <div class="row">

        {{-- CLOCK IN --}}
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <strong class="text-uppercase">Clock In</strong>
                </div>
                <div class="card-body">
                    @forelse($columns['clock_in'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                        @endphp
                        <div class="d-flex align-items-center mb-3">
                            {{-- Profile / Avatar --}}
                            <img 
                                src="{{ $employee->profile 
                                    ? asset('storage/profile/'.$employee->profile) 
                                    : 'https://ui-avatars.com/api/?name='.$employee->firstname }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                            >
                            <div>
                                <div class="fw-bold">{{ $employee->firstname }} {{ $employee->lastname }}</div>
                                <small class="text-muted">{{ $log['time_in'] }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- BREAK --}}
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <strong class="text-uppercase">Break (Out - In)</strong>
                </div>
                <div class="card-body">
                    @forelse($columns['break'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                        @endphp
                        <div class="d-flex align-items-center mb-3">
                            <img 
                                src="{{ $employee->profile 
                                    ? asset('storage/profile/'.$employee->profile) 
                                    : 'https://ui-avatars.com/api/?name='.$employee->firstname }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                            >
                            <div>
                                <div class="fw-bold">{{ $employee->firstname }} {{ $employee->lastname }}</div>
                                <small class="text-muted">{{ $log['break'] }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- CLOCK OUT --}}
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <strong class="text-uppercase">Clock Out</strong>
                </div>
                <div class="card-body">
                    @forelse($columns['clock_out'] as $item)
                        @php
                            $employee = $item['employee'];
                            $log = $item['log'];
                        @endphp
                        <div class="d-flex align-items-center mb-3">
                            <img 
                                src="{{ $employee->profile 
                                    ? asset('storage/profile/'.$employee->profile) 
                                    : 'https://ui-avatars.com/api/?name='.$employee->firstname }}"
                                class="rounded-circle me-2"
                                width="40"
                                height="40"
                            >
                            <div>
                                <div class="fw-bold">{{ $employee->firstname }} {{ $employee->lastname }}</div>
                                <small class="text-muted">{{ $log['time_out'] }}</small>
                            </div>
                        </div>
                    @empty
                        <small class="text-muted text-uppercase">No employees</small>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection


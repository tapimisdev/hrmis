@extends('admin.layouts.app')
@section('content')
<div class="container pt-4 px-3">
    <x-header title="All Approvers" subtitle="View all approvers in every units">
        <x-button-link :href="route('settings.approvers.index')" icon="fa-solid fa-arrow-left me-2" text="Back" variant="danger" />
    </x-header>
    <div class="my-4">
        <div class="accordion" id="approverAccordion">
            @foreach ($data as $unit => $levels)
                @php
                    $unitId = Str::slug($unit, '-');
                    $loopFirst = $loop->first ? 'show' : '';
                    $buttonExpanded = $loop->first ? 'true' : 'false';
                    $collapsed = $loop->first ? '' : 'collapsed';
                @endphp
                <div class="accordion-item shadow-sm border-0">
                    <h2 class="accordion-header" id="heading-{{ $unitId }}">
                        <button class="accordion-button {{ $collapsed }} fw-semibold text-uppercase"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse-{{ $unitId }}"
                            aria-expanded="{{ $buttonExpanded }}"
                            aria-controls="collapse-{{ $unitId }}">
                            {{ $unit }}
                        </button>
                    </h2>
                    <div id="collapse-{{ $unitId }}"
                        class="accordion-collapse collapse {{ $loopFirst }}"
                        aria-labelledby="heading-{{ $unitId }}"
                        data-bs-parent="#approverAccordion">
                        <div class="accordion-body">
                            @foreach ($levels as $level => $members)
                                <div class="border-start border-3 ps-3 pb-4">
                                    <div class="fw-bold text-muted text-uppercase mb-2">{{ $level }}</div>
                                    <div class="row g-3">
                                        @foreach ($members as $member)
                                            <a href="{{route('hris.employee.personal', ['employee_no' => $member['employee_no']])}}" class="col-md-3 text-decoration-none">
                                                <div class="p-3 border rounded-3 shadow-sm bg-light hover-card d-flex position-relative">
                                                    <div>
                                                        <div class="fw-semibold text-dark text-uppercase">{{ $member['name'] }}</div>
                                                        <div class="text-muted small text-capitalize mt-1">{{ $member['position'] }}</div>
                                                    </div>
                                                    <div>
                                                        <div class="profile">
                                                            <img src="{{ $member['profile'] }}" alt="" srcset="">
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
<style>
.profile {
    width: 65px;
    height: 65px;
    border: 2px; 
    background-color: #bbbbbbff;
    position: absolute;
    right: 12px;
    top: -20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}
</style>

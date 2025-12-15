@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <x-header title="Employee List" subtitle="Manage employee's informations in this module" >
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle"
                            type="button" 
                            id="employeeActionsDropdown" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="fa-solid fa-gear me-2"></i> Actions 
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end w-100 dropdown-menu-modern" aria-labelledby="employeeActionsDropdown">
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="{{ route('hris.import.index') }}">
                                <i class="fa-solid fa-file-import me-2"></i> Import
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="{{ route('hris.employee.salary') }}">
                                <i class="fa-solid fa-peso-sign me-2"></i> Update Salary
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item fw-bold text-uppercase d-flex align-items-center" href="{{ route('hris.employee.transfer') }}">
                                <i class="fa-solid fa-right-left me-2"></i> Transfer Unit
                            </a>
                        </li>
                    </ul>
                </div>
                <x-button-link 
                    :href="route('hris.employee.information')" 
                    icon="fa-solid fa-plus" 
                    text="Add Employee" 
                    variant="primary"
                />
            </div>
        </x-header>
        <hris-index url="{{route('hris.employee.index')}}"/>
    </div>
@endsection
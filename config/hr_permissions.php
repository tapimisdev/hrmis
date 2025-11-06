<?php

return [

    // Dashboard
    'dashboard' => ['view'],

    // HRIS Management
    'hris' => [
        'view',
        'create',
        'edit',
        'delete',
        'transfer_unit',
        'update_salary',
        'import_employee',
    ],

    // Timekeeping
    'timekeeping' => [
        'view',
        'adjust_time',
        'add_overtime',
        'record_leave',
        'mark_absent',
    ],

    // Salary & Payroll
    'salary_payroll' => [
        'view',
        'create',
        'delete',
    ],

    // Services
    'events_and_announcements' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'leave_approval' => [
        'view',
        'approve',
        'disapprove',
        'cancel',
    ],

    'pass_slip_approval' => [
        'view',
        'approve',
        'disapprove',
        'cancel',
    ],

    'overtime_approval' => [
        'view',
        'approve',
        'disapprove',
        'cancel',
    ],

    // Core Entities
    'division' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'unit' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'project' => [
        'view',
        'assign',
        'edit',
        'delete',
    ],

    'employment_type' => [
        'view',
        'edit',
    ],

    'position' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'role_and_permission' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'shift' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'weekly_schedule' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'holiday' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'earnings' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'deductions' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'leave_type' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'tranche' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'approvers' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    // Admin Management
    'admin' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

];

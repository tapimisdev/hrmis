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
        'import',
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
        'save',
    ],

    'offset_approval' => [
        'view',
        'save',
    ],

    'pass_slip_approval' => [
        'view',
        'save',
    ],

    'overtime_approval' => [
        'view',
        'save',
    ],

    'suspensions' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    // Core Entities
    'organization' => [
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

    'admin' => [
        'view',
        'create',
        'edit',
        'delete',
    ],

    'payroll_earnings' => [
        'view',
        'create',
        'update'
    ],
    'payroll_earnings_items' => [
        'view',
        'update'
    ],
    'payroll_taxes' => [
        'view',
        'create',
        'update'
    ],
    'payroll_taxes_items' => [
        'view',
        'update'
    ],
    'payroll_deductions' => [
        'view',
        'create',
        'update'
    ],
    'payroll_deductions_items' => [
        'view',
        'update'
    ],
    'correction' => [
        'view',
        'approval'
    ],
    'webtime' => [
        'create',
        'view'
    ]

];

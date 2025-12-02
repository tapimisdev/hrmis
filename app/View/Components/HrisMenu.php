<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;

class HrisMenu extends Component
{

    public $active;
    public $employee_no;
    public $menus;

    /**
     * Create a new component instance.
     */
    public function __construct($active, $empno)
    {
        $this->active = $active;
        $this->employee_no = $empno;
        $this->menus = $this->menuInfo();
    }

    private function menuInfo() {

        $employment_type = DB::table('employee_organization')
                                ->where('employee_no', $this->employee_no)
                                ->latest()
                                ->value('employment_type_id');

        $menus = DB::table('payroll_components')->get();
        $other_modules = DB::table('modules')->get();

        $currentYear = now()->year;

        $latest_year = DB::table('payroll_components_years')
            ->where('year', $currentYear)
            ->select('id', 'year')
            ->first()
            ->year ?? $currentYear;

        $payroll_components = [];
        $modules = [];

        foreach ($menus as $menu) {
            $route = $employment_type == 1 
                ? route('payroll-employee-components.index', [
                    'slug' => $menu->slug, 
                    'year' => $latest_year, 
                    'employee_no' => $this->employee_no
                ])
                : route('payroll-components.index', ['slug' => $menu->slug]);

            $payroll_components[] = [
                'name' => $menu->name,
                'route' => $route,
                'type' => $menu->type,
                'group' => $menu->group ?? [$menu->type ?? 'others'],
                'active' => $this->active == $menu->slug ? 'active' : '',
            ];
        }

        $grouped_components = [];
        foreach ($payroll_components as $component) {
            foreach ($component['group'] as $group) {
                $grouped_components[$group][] = $component;
            }
        }

        foreach($other_modules as $module) {
            $route = $employment_type == 1 ? 
                    '/admin/modules/'. $module->slug . '?tab=' . $module->slug . '&employee_no=' . $this->employee_no
                    : route('modules.index', ['slug' => $module->slug]);

            $modules[] = [
                'name' => $module->module_name,
                'route' => $route,
                'active' => $this->active == $module->slug ? 'active' : '',
            ];
        }

        return [
            [
                'name' => 'I. Employee Information',
                'route' => route('hris.employee.information', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'information' ? 'active' : '',
            ],
            [
                'name' => 'I. Personal Information',
                'route' => route('hris.employee.personal', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'personal' ? 'active' : '',
            ],
            [
                'name' => 'II. Family Background (A)',
                'route' => route('hris.employee.family', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'family' ? 'active' : '',
            ],
            [
                'name' => 'II. Family Background (B)',
                'route' => route('hris.employee.children', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'children' ? 'active' : '',
            ],
            [
                'name' => 'III. Educational Background',
                'route' => route('hris.employee.education', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'education' ? 'active' : '',
            ],
            [
                'name' => 'IV. Civil Service Eligibility',
                'route' => route('hris.employee.civil-service', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'civil-service' ? 'active' : '',
            ],
            [
                'name' => 'V. Work Experience',
                'route' => route('hris.employee.work-experience', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'work-experience' ? 'active' : '',
            ],
            [
                'name' => 'VI. Voluntary Work or Involvement in Civic / Non-Government / People / Voluntary Organizations',
                'route' => route('hris.employee.voluntary-works', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'voluntary-works' ? 'active' : '',
            ],
            [
                'name' => 'VII. Learning and Development (L&D) Interventions / Training Programs Attended',
                'route' => route('hris.employee.trainings', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'trainings' ? 'active' : '',
            ],
            [
                'name' => 'VIII. Skills or Hobbies',
                'route' => route('hris.employee.skills', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'skills' ? 'active' : '',
            ],
            [
                'name' => 'IX. Account Setup',
                'route' => route('hris.employee.account', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'account' ? 'active' : '',
            ],
            [
                'name' => 'X. Leave Credits',
                'route' => route('hris.employee.leave-credits', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'leave-credits' ? 'active' : '',
            ],
            [
                'name' => 'XI. Earnings & Taxes',
                'active' => '',
                'submenus' => $grouped_components,
            ],
            [
                'name' => 'XII. Deductions',
                'active' => '',
                'submenus' => $modules,
            ],
        ];

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.hris-menu');
    }
}

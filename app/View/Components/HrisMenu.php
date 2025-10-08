<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

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
        return [
            [
                'name' => 'Employee Information',
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
                'name' => 'Account Setup',
                'route' => route('hris.employee.account', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'account' ? 'active' : '',
            ],
            [
                'name' => 'Earnings',
                'route' => route('hris.employee.earnings', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'earnings' ? 'active' : '',
            ],
            [
                'name' => 'Deductions',
                'route' => route('hris.employee.deductions', ['employee_no' => $this->employee_no]),
                'active' => $this->active == 'deductions' ? 'active' : '',
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

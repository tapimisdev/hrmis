<?php

namespace App\Enums;

enum TableSettingsEnum: string
{
    case SALARY_ID    = 'salary_pay';
    case HAZARD_PA    = 'hazard_pay';
    case LONGETIVITY  = 'longetivity_pay';
    case PERA         = 'pera_allowance';
    case REPRESENTATION_ALLOWANCE = 'representation_allowance';
    case TRANSPORTATION_ALLOWANCE = 'transportation_allowance';

    // ID IN MODULE_TABS
    // TO GET EMPLOYEES RECORD; USE WHERE MODULE_TAB_ID = CASE
    case GSIS = '1';
    case PAGIBIG = '2';
    case PHILHEALTH = '3';


}
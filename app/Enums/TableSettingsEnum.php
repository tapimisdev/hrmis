<?php

namespace App\Enums;

enum TableSettingsEnum: string
{
    case SALARY_ID    = 'salary_pay';
    case HAZARD_PA    = 'hazard_pay';
    case LONGETIVITY  = 'longetivity_pay';
    case PERA         = 'pera_allowance';
    case RATA         = 'rata_allowance';
}
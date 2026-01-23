<?php

namespace App\Enums;

enum TableSettingsEnum: string
{
    case SALARY_ID    = 'salary_pay';
    case HAZARD_PA    = 'hazard_pay';
    case LONGETIVITY  = 'longetivity_pay';
    case PERA         = 'pera_allowance';
    case RATA         = 'rata_allowance';

    case TWO_PERCENT  = 'ewt_2%';
    case THREE_PERCENT  = 'percentage_tax_3%';
    case FIVE_PERCENT  = 'tax_ewt_5%';

}
<?php

namespace App\Enums;

enum FnEnum: int
{
    case TimeIn         = 0;
    case TimeOut        = 1;
    case BreakOut       = 2;
    case BreakIn        = 3;
    case OvertimeIn     = 4;
    case OvertimeOut    = 5;
}
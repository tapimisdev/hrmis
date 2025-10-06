<?php

namespace App\Enums;

enum PayrollStatusEnum: string
{
    case Draft        = 'draft';
    case Pending      = 'pending';
    case Approved     = 'approved';
    case ForReleasing = 'for_releasing';
    case Completed    = 'completed';
    case Cancelled    = 'cancelled';
}

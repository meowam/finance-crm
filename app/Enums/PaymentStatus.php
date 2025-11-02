<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Canceled = 'canceled';
}

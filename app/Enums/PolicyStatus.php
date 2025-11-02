<?php

namespace App\Enums;

enum PolicyStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Canceled = 'canceled';
}

<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class UserNotification extends DatabaseNotification
{
    protected $table = 'notifications';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
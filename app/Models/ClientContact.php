<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'type', 'value', 'label', 'notes'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

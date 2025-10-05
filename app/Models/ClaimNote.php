<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id', 'user_id', 'visibility', 'note'
    ];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}

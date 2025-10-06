<?php

// namespace App\Models;

// use App\Models\Client;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class Activity extends Model
// {
//     use HasFactory;

//     protected $fillable = [
//         'client_id', 'policy_id', 'claim_id', 'owner_id',
//         'activity_type', 'subject', 'description',
//         'status', 'due_at', 'completed_at', 'metadata'
//     ];

//     protected $casts = [
//         'metadata' => 'array'
//     ];

//     public function client()
//     {
//         return $this->belongsTo(Client::class);
//     }

//     public function policy()
//     {
//         return $this->belongsTo(Policy::class);
//     }

//     public function claim()
//     {
//         return $this->belongsTo(Claim::class);
//     }
// }

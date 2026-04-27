<?php
namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimNote extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'claim_id',
        'user_id',
        'visibility',
        'note',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivityLogLabel(): string
    {
        $claimNumber = $this->claim?->claim_number ?: ('заява #' . $this->claim_id);

        return "Нотатка до {$claimNumber}";
    }
}

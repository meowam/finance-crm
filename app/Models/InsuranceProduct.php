<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sales_enabled',
        'metadata',
        'category_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sales_enabled' => 'boolean',
    ];

    // зв’язок із категорією
    public function category()
    {
        return $this->belongsTo(InsuranceCategory::class, 'category_id');
    }

    // зв’язок із пропозиціями
    public function offers()
    {
        return $this->hasMany(InsuranceOffer::class);
    }

    // зв’язок із полісами (реальні оформлення)
    public function policies()
    {
        return $this->hasMany(Policy::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'sales_enabled',
        'metadata',
    ];

    protected $casts = [
        'sales_enabled' => 'boolean',
        'metadata'      => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(InsuranceCategory::class, 'category_id');
    }

    public function offers()
    {
        return $this->hasMany(InsuranceOffer::class, 'insurance_product_id');
    }

    public function policies()
    {
        return $this->hasManyThrough(
            Policy::class,
            InsuranceOffer::class,
            'insurance_product_id', 
            'insurance_offer_id',   
            'id',                   
            'id'                    
        );
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCategory extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = $value !== null
            ? strtoupper($value)
            : null;
    }
    public function products()
    {
        return $this->hasMany(InsuranceProduct::class, 'category_id');
    }
}

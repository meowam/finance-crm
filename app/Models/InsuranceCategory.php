<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceCategory extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function products()
    {
        return $this->hasMany(InsuranceProduct::class, 'category_id');
    }
}

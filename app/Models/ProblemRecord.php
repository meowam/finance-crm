<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemRecord extends Model
{
    protected $table = 'problem_records';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'SKILL';

    protected $primaryKey = 'id_skill';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'state',
        'type',
        'quantitative_level',
        'qualitative_level',
        'description',
        'id_area',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'COUNTRY';

    protected $primaryKey = 'id_country';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'state',
    ];
}

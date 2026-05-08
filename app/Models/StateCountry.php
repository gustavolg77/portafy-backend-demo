<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StateCountry extends Model
{
    protected $table = 'STATE_COUNTRY';

    protected $primaryKey = 'id_state_country';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_country',
        'name',
        'state',
    ];
}

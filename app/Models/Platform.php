<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $table = 'PLATFORM';

    protected $primaryKey = 'id_platform';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'url_platform',
    ];
}

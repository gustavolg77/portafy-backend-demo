<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    protected $table = 'CREDENTIAL';

    protected $primaryKey = 'id_credentials';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_profile',
        'password',
        'old_password',
        'keyword',
    ];

    protected $hidden = [
        'password',
        'old_password',
        'keyword',
    ];
}

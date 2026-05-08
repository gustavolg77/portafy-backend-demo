<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $table = 'PREFERENCE';

    protected $primaryKey = 'id_preference';

    public $timestamps = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'description',
        'type',
        'visibility',
        'color',
        'id_profile',
    ];

    protected $casts = [
        'visibility' => 'boolean',
    ];
}

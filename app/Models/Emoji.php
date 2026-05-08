<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emoji extends Model
{
    protected $table = 'EMOJI';

    protected $primaryKey = 'id_emoji';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'state',
    ];
}

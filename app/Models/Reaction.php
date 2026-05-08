<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $table = 'REACTION';

    protected $primaryKey = 'id_reaction';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_reactor',
        'id_emoji',
        'id_publication',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'id_publication', 'id_publication');
    }

    public function reactor()
    {
        return $this->belongsTo(Profile::class, 'id_reactor', 'id_profile');
    }

    public function emoji()
    {
        return $this->belongsTo(Emoji::class, 'id_emoji', 'id_emoji');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedPublication extends Model
{
    protected $table = 'SAVED';

    protected $primaryKey = 'id_saved';

    public $incrementing = true;

    protected $keyType = 'int';

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_publication',
        'id_profile',
        'id_project',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'id_publication', 'id_publication');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }
}

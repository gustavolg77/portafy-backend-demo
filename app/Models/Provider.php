<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'PROVIDER';

    protected $primaryKey = 'id_provider';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'provider',
        'id_profile',
        'active',
        'provider_user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $table = 'USER_ROLE';

    protected $primaryKey = 'id_user_role';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'id_role',
    ];

    public function user()
    {
        return $this->belongsTo(Usuario::class, 'id_user', 'id_user');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }
    public function profile()
    {
        return $this->hasOne(Profile::class, 'id_user_rol', 'id_user_role');
    }
}

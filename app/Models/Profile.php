<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'PROFILE';

    protected $primaryKey = 'id_profile';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'last_name',
        'biography',
        'birthdate',
        'profile_photo',
        'cover_photo',
        'completed_profile',
        'id_state_country',
        'id_job_title',
        'id_user_rol',
        'id_company',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'completed_profile' => 'boolean',
    ];

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'id_job_title', 'id_job_title');
    }

    public function userRole()
    {
        return $this->belongsTo(UserRole::class, 'id_user_rol', 'id_user_role');
    }

    public function stateCountry()
    {
        return $this->belongsTo(StateCountry::class, 'id_state_country', 'id_state_country');
    }

    public function credential()
    {
        return $this->hasOne(Credential::class, 'id_profile', 'id_profile');
    }

    public function providers()
    {
        return $this->hasMany(Provider::class, 'id_profile', 'id_profile');
    }

    public function cvs()
    {
        return $this->hasMany(Cv::class, 'id_profile', 'id_profile');
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

}

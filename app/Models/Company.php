<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'COMPANY';
    protected $primaryKey = 'id_company';

    protected $fillable = [
        'name', 'description', 'mission', 'vision', 'logo_url', 'industry', 'banner_url',
        'city', 'id_country', 'phone_prefix', 'phone', 'website', 'state',
    ];

    public function recruiter()
    {
        return $this->hasOne(Profile::class, 'id_company', 'id_company');
    }
}

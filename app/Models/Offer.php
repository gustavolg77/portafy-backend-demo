<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'OFFER';
    protected $primaryKey = 'id_offer';

    protected $fillable = [
        'title',
        'description',
        'type',
        'modalidad',
        'ubicacion',
        'salary_min',
        'salary_max',
        'currency',
        'nivel',
        'banner_url',
        'area',
        'show_salary',
        'quota_quantity',
        'closed_at',
        'state',
        'id_profile',
    ];

    protected $casts = [
        'show_salary' => 'boolean',
        'closed_at'   => 'date',
        'salary_min'  => 'integer',
        'salary_max'  => 'integer',
    ];

    // Relación con el perfil que publicó la oferta
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    // Skills requeridas via OFFER_DETAIL
    public function offerDetails()
    {
        return $this->hasMany(OfferDetail::class, 'id_offer', 'id_offer');
    }

    public function skills()
    {
        return $this->hasManyThrough(
            Skill::class,
            OfferDetail::class,
            'id_offer',
            'id_skill',
            'id_offer',
            'id_skill'
        );
    }
}

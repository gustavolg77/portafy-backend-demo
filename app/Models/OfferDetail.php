<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferDetail extends Model
{
    protected $table = 'OFFER_DETAIL';
    protected $primaryKey = 'id_offer_detail';

    public $timestamps = false;

    protected $fillable = [
        'id_offer',
        'id_skill',
        'id_job_title',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'id_offer', 'id_offer');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'id_skill', 'id_skill');
    }
}

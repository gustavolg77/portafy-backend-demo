<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Postulation extends Model
{
    protected $table      = 'POSTULATION';
    protected $primaryKey = 'id_postulation';

    protected $fillable = [
        'id_offer',
        'id_postulant',
        'id_cv',
        'reason',
        'state',
    ];

    /* ─── Relaciones ─── */

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'id_offer', 'id_offer');
    }

    public function postulant()
    {
        return $this->belongsTo(Profile::class, 'id_postulant', 'id_profile');
    }

    public function cv()
    {
        return $this->belongsTo(Cv::class, 'id_cv', 'id_cv');
    }
}

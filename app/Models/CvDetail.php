<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvDetail extends Model
{
    protected $table = 'CV_DETAIL';
    protected $primaryKey = 'id_cv_detail';

    protected $fillable = [
        'id_cv',
        'id_project',
        'id_experience',
        'id_certificate',
        'id_university_career',
        'id_social_network',
        'id_skill_profile',
        'visibility',
    ];

    protected $casts = [
        'visibility' => 'boolean',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'id_cv', 'id_cv');
    }
}

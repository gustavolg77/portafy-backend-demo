<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvCustomEntry extends Model
{
    protected $table      = 'CV_CUSTOM_ENTRY';
    protected $primaryKey = 'id_cv_custom_entry';

    protected $fillable = [
        'id_cv',
        'entry_type',
        'title',
        'subtitle',
        'description',
        'date_start',
        'date_end',
        'is_current',
        'visibility',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'visibility' => 'boolean',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'id_cv', 'id_cv');
    }
}

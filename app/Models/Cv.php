<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cv extends Model
{
    protected $table = 'CV';
    protected $primaryKey = 'id_cv';

    protected $fillable = [
        'name_cv',
        'template',
        'font',
        'state',
        'visible',
        'archive_pdf',
        'description',
        'cv_url',
        'id_profile',
    ];

    protected $casts = [
        'state'   => 'boolean',
        'visible' => 'boolean',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function details(): HasMany
    {
        return $this->hasMany(CvDetail::class, 'id_cv', 'id_cv');
    }

    // Scope para filtrar por perfil
    public function scopeForProfile($query, int $profileId)
    {
        return $query->where('id_profile', $profileId);
    }
}

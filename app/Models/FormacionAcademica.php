<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FormacionAcademica extends Model
{
    protected $table = 'UNIVERSITY_CAREER';

    protected $primaryKey = 'id_university_career';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'training_type',
        'start_date',
        'end_date',
        'visibility',
        'id_university',
        'id_career',
        'id_profile',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'visibility' => 'boolean',
    ];

    protected $hidden = [
        'id_university_career',
        'id_profile',
        'id_university',
        'id_career',
        'training_type',
        'start_date',
        'end_date',
        'visibility',
    ];

    protected $appends = [
        'id',
        'usuario_id',
        'nivel_formacion',
        'tipo_formacion',
        'institucion',
        'nombre_programa',
        'nombre_carrera',
        'careerName',
        'fecha_inicio',
        'fecha_fin',
        'actualmente',
        'type',
        'is_current',
        'isCurrent',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function university()
    {
        return $this->belongsTo(University::class, 'id_university', 'id_university');
    }

    public function career()
    {
        return $this->belongsTo(Career::class, 'id_career', 'id_career');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->with(['university', 'career', 'profile.userRole'])
            ->whereHas('profile.userRole', fn (Builder $builder) => $builder->where('id_user', $userId));
    }

    public function getIdAttribute(): int
    {
        return (int) $this->getKey();
    }

    public function getUsuarioIdAttribute(): ?int
    {
        return $this->profile?->userRole?->id_user;
    }

    public function getNivelFormacionAttribute(): ?string
    {
        return $this->getRawOriginal('training_type');
    }

    public function getTipoFormacionAttribute(): ?string
    {
        return $this->nivel_formacion;
    }

    public function getInstitucionAttribute(): ?string
    {
        return $this->university?->name;
    }

    public function getNombreProgramaAttribute(): ?string
    {
        return $this->career?->name;
    }

    public function getNombreCarreraAttribute(): ?string
    {
        return $this->nombre_programa;
    }

    public function getCareerNameAttribute(): ?string
    {
        return $this->nombre_programa;
    }

    public function getFechaInicioAttribute(): mixed
    {
        return $this->getAttributeValue('start_date');
    }

    public function getFechaFinAttribute(): mixed
    {
        return $this->getAttributeValue('end_date');
    }

    public function getActualmenteAttribute(): bool
    {
        return empty($this->getRawOriginal('end_date'));
    }

    public function getTypeAttribute(): ?string
    {
        return $this->nivel_formacion;
    }

    public function getIsCurrentAttribute(): bool
    {
        return $this->actualmente;
    }

    public function getIsCurrentCamelAttribute(): bool
    {
        return $this->actualmente;
    }
}

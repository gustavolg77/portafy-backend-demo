<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $table = 'EXPERIENCE';

    protected $primaryKey = 'id_experience';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'type',
        'company',
        'title',
        'description',
        'start_date',
        'end_date',
        'state',
        'id_profile',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $hidden = [
        'id_experience',
        'id_profile',
        'description',
        'start_date',
        'end_date',
        'state',
    ];

    protected $appends = [
        'id',
        'usuario_id',
        'empresa',
        'cargo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'actualmente',
        'company',
        'title',
        'type',
        'type_label',
        'is_freelance',
        'is_current',
        'isCurrent',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->with('profile.userRole')
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

    public function getEmpresaAttribute(): ?string
    {
        return $this->getRawOriginal('company');
    }

    public function getCargoAttribute(): ?string
    {
        return $this->getRawOriginal('title');
    }

    public function getDescripcionAttribute(): ?string
    {
        return $this->getRawOriginal('description');
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

    public function getCompanyAttribute(): ?string
    {
        return $this->empresa;
    }

    public function getTitleAttribute(): ?string
    {
        return $this->cargo;
    }

    public function getTypeAttribute(): string
    {
        return match (mb_strtolower((string) $this->getRawOriginal('type'))) {
            'academic', 'academico' => 'academico',
            'freelance', 'independiente' => 'freelance',
            default => 'profesional',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'academico' => 'Academico',
            'freelance' => 'Freelance',
            default => 'Profesional',
        };
    }

    public function getIsFreelanceAttribute(): bool
    {
        return $this->type === 'freelance';
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

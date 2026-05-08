<?php

namespace App\Models;

use App\Support\OfficialSchema;
use App\Support\ProfileCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Habilidad extends Model
{
    protected $table = 'SKILL_PROFILE';

    protected $primaryKey = 'id_skill_profile';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_skill',
        'id_profile',
        'level',
        'visibility',
    ];

    protected $hidden = [
        'id_skill_profile',
        'id_profile',
        'id_skill',
    ];

    protected $appends = [
        'id',
        'usuario_id',
        'nombre',
        'descripcion',
        'description',
        'tipo',
        'nivel_texto',
        'nivel_label',
        'nivel_numero',
        'nivel_puntos',
        'level_dots',
        'nivel',
        'nivel_cuantitativo',
        'nivel_cualitativo',
        'level',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'id_skill', 'id_skill');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->with(['skill', 'profile.userRole'])
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

    public function getNombreAttribute(): string
    {
        return (string) ($this->skill?->name ?? '');
    }

    public function getDescripcionAttribute(): string
    {
        return (string) ($this->skill?->description ?? '');
    }

    public function getDescriptionAttribute(): string
    {
        return $this->descripcion;
    }

    public function getTipoAttribute(): string
    {
        return OfficialSchema::apiSkillType($this->skill?->type);
    }

    public function getNivelTextoAttribute(): string
    {
        return OfficialSchema::apiSkillLevelText($this->getRawOriginal('level'));
    }

    public function getNivelLabelAttribute(): string
    {
        $level = collect(ProfileCatalog::skillLevels())
            ->firstWhere('value', $this->nivel_texto);

        return $level['label'] ?? ucfirst($this->nivel_texto);
    }

    public function getNivelNumeroAttribute(): int
    {
        return OfficialSchema::apiSkillLevelNumber($this->getRawOriginal('level'));
    }

    public function getNivelAttribute(): int
    {
        return match ($this->getRawOriginal('level')) {
            'senior' => 4,
            'mid' => 3,
            default => 2,
        };
    }

    public function getNivelPuntosAttribute(): int
    {
        $level = collect(ProfileCatalog::skillLevels())
            ->firstWhere('value', $this->nivel_texto);

        return (int) ($level['dots'] ?? 1);
    }

    public function getLevelDotsAttribute(): int
    {
        return $this->nivel_puntos;
    }

    public function getNivelCuantitativoAttribute(): string
    {
        return $this->nivel_texto;
    }

    public function getNivelCualitativoAttribute(): int
    {
        return $this->nivel_numero;
    }

    public function getLevelAttribute(): string
    {
        return $this->nivel_texto;
    }
}

<?php

namespace App\Models;

use App\Support\OfficialSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'PROJECT';

    protected $primaryKey = 'id_project';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'title',
        'description',
        'repository_url',
        'url_demo',
        'image',
        'state',
        'url_photo_main',
        'id_profile',
        'visibility',
    ];

    protected $casts = [
        'visibility' => 'boolean',
    ];

    protected $hidden = [
        'id_project',
        'id_profile',
        'title',
        'description',
        'repository_url',
        'image',
        'state',
        'url_photo_main',
        'visibility',
    ];

    protected $appends = [
        'id',
        'usuario_id',
        'titulo',
        'descripcion',
        'tecnologias',
        'url_repositorio',
        'imagen',
        'estado',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function skills()
    {
        return $this->belongsToMany(
            Skill::class,
            'PROJECT_SKILL',
            'id_project',
            'id_skill',
            'id_project',
            'id_skill'
        );
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->with(['skills', 'profile.userRole'])
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

    public function getTituloAttribute(): string
    {
        return (string) $this->getRawOriginal('title');
    }

    public function getDescripcionAttribute(): ?string
    {
        return $this->getRawOriginal('description');
    }

    public function getTecnologiasAttribute(): string
    {
        return $this->skills->pluck('name')->implode(', ');
    }

    public function getUrlRepositorioAttribute(): ?string
    {
        return $this->getRawOriginal('repository_url');
    }

    public function getImagenAttribute(): ?string
    {
        return $this->getRawOriginal('image') ?: $this->getRawOriginal('url_photo_main');
    }

    public function getEstadoAttribute(): string
    {
        return OfficialSchema::apiProjectState($this->getRawOriginal('state'));
    }
}

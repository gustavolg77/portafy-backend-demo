<?php

namespace App\Models;

use App\Support\ProfileCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Social extends Model
{
    protected $table = 'SOCIAL_NETWORK';

    protected $primaryKey = 'id_social_networks';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_profile',
        'id_platform',
        'url',
        'public',
    ];

    protected $casts = [
        'public' => 'boolean',
    ];

    protected $hidden = [
        'id_social_networks',
        'id_profile',
        'id_platform',
        'public',
    ];

    protected $appends = [
        'id',
        'usuario_id',
        'plataforma',
        'platform_key',
        'platform_name',
        'platform_icon',
        'platform_color',
        'display_name',
        'nombre_plataforma',
        'url_plataforma',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'id_platform', 'id_platform');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->with(['platform', 'profile.userRole'])
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

    public function getNombrePlataformaAttribute(): ?string
    {
        return $this->platform_name;
    }

    public function getPlataformaAttribute(): ?string
    {
        return $this->platform_key;
    }

    public function getPlatformKeyAttribute(): string
    {
        return ProfileCatalog::normalizePlatform($this->platform?->name);
    }

    public function getPlatformNameAttribute(): string
    {
        return ProfileCatalog::platformMeta($this->platform?->name)['label'];
    }

    public function getPlatformIconAttribute(): string
    {
        return ProfileCatalog::platformMeta($this->platform?->name)['icon'];
    }

    public function getPlatformColorAttribute(): string
    {
        return ProfileCatalog::platformMeta($this->platform?->name)['color'];
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->platform_name;
    }

    public function getUrlPlataformaAttribute(): ?string
    {
        return $this->getRawOriginal('url');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->url_plataforma;
    }

}

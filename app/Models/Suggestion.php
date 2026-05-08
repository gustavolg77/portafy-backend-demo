<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    protected $table = 'SUGGESTION';

    protected $primaryKey = 'id_suggestion';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_profile',
        'description',
        'type',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function scopeByType($query, string $type)
    {
        if ($type && $type !== 'todos') {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function scopeByProfile($query, int $profileId)
    {
        if ($profileId) {
            return $query->where('id_profile', $profileId);
        }
        return $query;
    }

    public function scopeSearch($query, string $search)
    {
        if ($search) {
            return $query->where('description', 'like', "%{$search}%");
        }
        return $query;
    }

    public function getMeta(): array
    {
        $typeLabels = [
            'agregar' => 'Nueva funcionalidad',
            'idea' => 'Idea',
            'mejora' => 'Mejora',
            'eliminar' => 'Eliminar funcionalidad',
        ];

        $typeColors = [
            'agregar' => 'badge--blue',
            'idea' => 'badge--purple',
            'mejora' => 'badge--green',
            'eliminar' => 'badge--red',
        ];

        return [
            'typeLabel' => $typeLabels[$this->type] ?? 'Desconocido',
            'badgeClass' => $typeColors[$this->type] ?? 'badge--gray',
            'badge' => $typeLabels[$this->type] ?? 'Desconocido',
        ];
    }

    public function attended()
    {
        return $this->hasMany(Attended::class, 'id_suggestion', 'id_suggestion');
    }
}

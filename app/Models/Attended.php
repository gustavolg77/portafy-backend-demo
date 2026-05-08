<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attended extends Model
{
    protected $table = 'ATTENDED';

    protected $primaryKey = 'id_attended';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'id_suggestion',
        'id_report',
        'id_administrator',   // ← era id_profile, no existe en la tabla
        'id_preference',
        'state',
        'action_taken',       // ← era note, no existe en la tabla
        'test_url',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id_attended'      => 'integer',
        'id_suggestion'    => 'integer',
        'id_administrator' => 'integer',
        'state'            => 'string',
        'action_taken'     => 'string',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    /**
     * Relación con la sugerencia atendida.
     */
    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(Suggestion::class, 'id_suggestion', 'id_suggestion');
    }

    /**
     * Relación con el perfil del administrador.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'id_administrator', 'id_profile'); // ← FK corregida
    }

    public const STATES = [
        'accepted'      => 'Aceptada',
        'rejected'      => 'Rechazada',
        'in_discussion' => 'En discusión',
        'higher'        => 'Escalada',
        'ignored'       => 'Ignorada',
    ];
}
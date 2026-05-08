<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicationComment extends Model
{
    protected $table = 'COMMENT';

    protected $primaryKey = 'id_comment';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_publication',
        'id_commentator',
        'comment',
        'removed_at',
    ];

    protected $casts = [
        'removed_at' => 'datetime',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'id_publication', 'id_publication');
    }

    public function commentator()
    {
        return $this->belongsTo(Profile::class, 'id_commentator', 'id_profile');
    }
}

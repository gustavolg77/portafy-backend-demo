<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicationDetail extends Model
{
    protected $table = 'PUBLICATION_DETAIL';

    protected $primaryKey = 'id_publication_detail';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_publicized',
        'id_offer',
        'id_project',
        'id_publication',
        'id_cv',
        'id_experience',
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'id_publication', 'id_publication');
    }

    public function project()
    {
        return $this->belongsTo(Proyecto::class, 'id_project', 'id_project');
    }

    public function experience()
    {
        return $this->belongsTo(Experience::class, 'id_experience', 'id_experience');
    }
}

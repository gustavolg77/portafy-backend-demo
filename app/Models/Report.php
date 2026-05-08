<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = 'REPORT';

    protected $primaryKey = 'id_report';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_comment',
        'id_response',
        'id_profile',
        'id_publication',
        'id_project',
        'id_message',
        'id_group',
        'id_reported_user',
        'id_portfolio',
        'description',
        'tests_url',
        'created_at',
        'motivo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function reporterProfile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function reportedProfile()
    {
        return $this->belongsTo(Profile::class, 'id_reported_user', 'id_profile');
    }

    public function project()
    {
        return $this->belongsTo(Proyecto::class, 'id_project', 'id_project');
    }

    public function publication()
    {
        return $this->belongsTo(Publication::class, 'id_publication', 'id_publication');
    }

    public function attendeds()
    {
        return $this->hasMany(Attended::class, 'id_report', 'id_report');
    }
}

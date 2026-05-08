<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSkill extends Model
{
    protected $table = 'PROJECT_SKILL';

    protected $primaryKey = 'id_project_skill';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_project',
        'id_skill',
    ];
}

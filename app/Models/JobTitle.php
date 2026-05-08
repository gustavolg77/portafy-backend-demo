<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    protected $table = 'JOB_TITLE';

    protected $primaryKey = 'id_job_title';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'id_area',
    ];
}

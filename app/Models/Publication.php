<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $table = 'PUBLICATION';

    protected $primaryKey = 'id_publication';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'description',
        'outstanding',
        'visibility',
        'state',
        'id_profile',
    ];

    protected $casts = [
        'outstanding' => 'boolean',
        'visibility' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('state', 'published')->where('visibility', true);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'id_profile', 'id_profile');
    }

    public function detail()
    {
        return $this->hasOne(PublicationDetail::class, 'id_publication', 'id_publication');
    }

    public function comments()
    {
        return $this->hasMany(PublicationComment::class, 'id_publication', 'id_publication')
            ->whereNull('removed_at')
            ->latest('created_at');
    }

    public function latestComment()
    {
        return $this->hasOne(PublicationComment::class, 'id_publication', 'id_publication')
            ->whereNull('removed_at')
            ->latestOfMany('created_at');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'id_publication', 'id_publication');
    }

    public function saves()
    {
        return $this->hasMany(SavedPublication::class, 'id_publication', 'id_publication');
    }
}

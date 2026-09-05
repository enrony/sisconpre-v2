<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilesUsers extends Model
{
    use HasFactory;

    protected $table = 'users_profiles';

    protected $with = ['profile'];

    protected $fillable = [
        'users_id', 'profiles_id', 'estatus',
    ];

    public function profile()
    {
        return $this->belongsTo(Profiles::class, 'profiles_id');
    }

    public function scopeWhereIsSU($query)
    {
        return $query->whereHas('profile', function ($query) {
            $query->where('su', true);
        });
    }
}

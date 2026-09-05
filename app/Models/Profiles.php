<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profiles extends Model
{
    use HasFactory;

    protected $table = 'profiles';

    protected $with = ['modules_actions_profiles', 'acciones'];

    protected $fillable = [
        'name', 'admin', 'estatus', 'su', 'description',
    ];

    protected $casts = [
        'admin' => 'boolean',
        'su' => 'boolean',
    ];

    public function modules_actions_profiles()
    {
        return $this->hasMany(ModulesActionsProfiles::class, 'profiles_id');
    }

    public function acciones()
    {
        return $this->belongsToMany(ModulesActions::class, 'modules_actions_profiles', 'profiles_id', 'modules_actions_id')->withPivot('id', 'estatus');
    }

    public static function lista($request)
    {

        $query1 = (new static)::selectRaw("profiles.*, date_format(profiles.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(profiles.updated_at, '%Y-%m-%d %H:%i:%s') as updated");
        if (isset($request->term)) {
            $query1 = $query1->when($request->term, function ($query, $term) {
                $query->where('profiles.name', 'LIKE', '%'.$term.'%');
            });
        }
        $query1 = $query1->get();

        $queryAll = collect($query1);

        return $queryAll;

    }
}

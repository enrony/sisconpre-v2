<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulesActionsProfiles extends Model
{
    use HasFactory;

    protected $with = ['module_actions'];

    protected $fillable = [
        'profiles_id', 'modules_actions_id', 'estatus',
    ];

    public function module_actions()
    {
        return $this->belongsTo(ModulesActions::class, 'modules_actions_id');
    }
}

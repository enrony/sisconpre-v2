<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModulesActions extends Model
{
    use HasFactory;

    protected $fillable = [
        'modules_id',
        'action_id',
        'estatus',
    ];

    public function module()
    {
        return $this->belongsTo(Modules::class, 'modules_id');
    }

    public function action()
    {
        return $this->belongsTo(Action::class);
    }
}

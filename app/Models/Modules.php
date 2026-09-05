<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    use HasFactory;

    protected $with = ['actions', 'children', 'acciones'];

    protected $fillable = [
        'name',
        'descripcion',
        'clave',
        'route',
        'icon',
        'active',
        'padre',
        'subpadre',
        'visible_menu',
        'route_url',
        'style',
        'estatus',
        'order',
        'method_post',
        'multiple_selection',
        'multiple_selection_actions',
    ];

    protected $casts = [
        'padre' => 'boolean',
        'subpadre' => 'boolean',
        'active' => 'boolean',
        'visible_menu' => 'boolean',
        'method_post' => 'boolean',
        'multiple_selection' => 'object',
        'multiple_selection_actions' => 'object',
    ];

    protected $appends = [
        'nivel_modulo',
    ];

    public function getNivelModuloAttribute()
    {
        $tipo = '';
        if ($this->padre) {
            $tipo = 'Principal';
        }
        if ($this->subpadre) {
            $tipo = 'Intermendio';
        }
        if (! $this->padre && ! $this->subpadre) {
            $tipo = 'Final';
        }

        return $tipo;
    }

    public function actions()
    {
        return $this->hasMany(ModulesActions::class);
    }

    public static function getDataMeues()
    {
        return Modules::where('estatus', 1)->where('active', 1)->get()->transform(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'route' => $row->route,
                'actions' => $row->actions,
                'clave' => $row->clave,
                'method_post' => $row->method_post,
            ];
        });
    }

    public function children()
    {
        return $this->belongsToMany(Modules::class, 'modules_relations', 'modules_father_id', 'modules_son_id')->select('modules.id', 'modules.padre', 'modules.name', 'modules.clave', 'modules.route', 'method_post');
    }

    public function acciones()
    {
        return $this->belongsToMany(Action::class, 'modules_actions', 'modules_id', 'action_id')->withPivot('estatus', 'id');
        // ->select('modules.id', 'modules.padre', 'modules.name', 'modules.clave', 'modules.route', 'method_post')
    }

    public function parents()
    {
        return $this->belongsToMany(Modules::class, 'modules_relations', 'modules_son_id', 'modules_father_id');
    }

    public static function lista($request)
    {
        $query = (new static)::selectRaw('modules.*, modules.created_at as created_at')
                // ->where('modules.estatus', 1)
            ->latest('modules.created_at')->without('children')
            ->get();

        return $query;
    }
}

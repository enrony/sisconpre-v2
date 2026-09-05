<?php

namespace App\Models;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tiposDocumentos extends Model
{
    use HasFactory;

    protected $fillable = [
        'sigla', 'nombre', 'country_id', 'estatus', 'grupos_trabajos_user_id',
    ];

    protected $table = 'tipos_documentos';

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public static function listaDocumentos($request)
    {

        $su = Controller::isSuperUsuario(auth()->user()->id);

        $GruposTrabajoUser = Controller::obtenerGrupoTrabajo();

        $select = "tipos_documentos.*, date_format(tipos_documentos.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(tipos_documentos.updated_at, '%Y-%m-%d %H:%i:%s') as updated";

        $query1 = (new static)::selectRaw("{$select}")
            ->when($request->term, function ($query, $term) {
                $query->where('tipos_documentos.nombre', 'LIKE', '%'.$term.'%');
            })
            ->where('tipos_documentos.estatus', 1)
            ->whereNull('grupos_trabajos_user_id')
            ->with(['country'])
            ->get();

        $query2 = (new static)::selectRaw("{$select}")
            ->when($request->term, function ($query, $term) {
                $query->where('tipos_documentos.nombre', 'LIKE', '%'.$term.'%');
            })
            ->where('tipos_documentos.estatus', 1)
            ->with(['country'])
            ->join('grupos_trabajos_users', function ($j) use ($GruposTrabajoUser, $su) {
                $j->on('grupos_trabajos_users.id', '=', 'tipos_documentos.grupos_trabajos_user_id');
                if (! $su) {
                    $j->where('grupos_trabajos_users.idgrupo_trabajo', $GruposTrabajoUser->idgrupo_trabajo);
                }
            })
            ->get();

        $queryAll = collect($query1);
        $queryAll = $queryAll->merge($query2);

        return $queryAll;

    }
}

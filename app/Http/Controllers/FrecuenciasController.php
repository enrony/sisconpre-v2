<?php

namespace App\Http\Controllers;

use App\Http\Requests\FrecuenciasRequest;
use App\Models\Frecuencias;
use App\Models\TipoFrecuenciaPrestamo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class FrecuenciasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Frecuencias $frecuencias)
    {
        \App::setLocale('es');

        return Inertia\Inertia::render(
            'Frecuencias',
            [
                'lista' => $frecuencias::selectRaw("frecuencias.*, lower( nombre ) as nombre_lower, date_format(frecuencias.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(frecuencias.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) {
                        $query->where('frecuencias.nombre', 'LIKE', '%'.$term.'%');
                    })->where('frecuencias.estatus', 1)
                    ->with(['tipo'])
                    ->latest('frecuencias.created_at')->paginate(50),
                'messages' => __('messages'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(FrecuenciasRequest $request)
    {

        if ($request->has('id') && $request->input('id') > 0) {
            Frecuencias::find($request->input('id'))->update($request->all());
            session()->flash('flash.message', 'Registro actualizado!');
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            Frecuencias::create($request->all());
            session()->flash('flash.message', 'Registro creado!');
        }

        session()->flash('flash.type', 'success');

        return Redirect::route('frecuencias', ['page' => $request->input('paginaActual')]);

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Frecuencias $frecuencias)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Frecuencias $frecuencias)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Frecuencias $frecuencias)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Frecuencias $frecuencias, $id, $page)
    {
        //
        $row = $frecuencias->findOrFail($id);
        if (! $row->por_defecto) {
            $row->delete();
            session()->flash('flash.type', 'success');
            session()->flash('flash.message', 'Registro eliminado!');
        } else {
            session()->flash('flash.type', 'error');
            session()->flash('flash.message', 'No se puede eliminar el registro seleccionado');
        }

        return Redirect::route('frecuencias', ['page' => $page]);
    }

    public function tables(TipoFrecuenciaPrestamo $TipoFrecuenciaPrestamo)
    {

        $TipoFrecuenciaPrestamoAll = $TipoFrecuenciaPrestamo->get();

        return compact('TipoFrecuenciaPrestamoAll');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoPrestamoRequest;
use App\Models\TipoFrecuenciaPrestamo;
use App\Models\TipoPrestamo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class TipoPrestamoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, TipoPrestamo $TipoPrestamo)
    {

        $table = 'tipo_prestamos';

        return Inertia\Inertia::render(
            'TipoPrestamo',
            [
                'lista' => $TipoPrestamo::selectRaw("{$table}.*, lower( descripcion ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) use ($table) {
                        $query->where("{$table}.descripcion", 'LIKE', '%'.$term.'%');
                    })->where("{$table}.estatus", 1)
                    ->with(['frecuencias'])
                    ->latest("{$table}.created_at")->paginate(50),
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
    public function store(TipoPrestamoRequest $request)
    {

        if ($request->has('id') && $request->input('id') > 0) {
            TipoPrestamo::find($request->input('id'))->update($request->all());
            session()->flash('flash.message', 'Registro actualizado!');
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            TipoPrestamo::create($request->all());
            session()->flash('flash.message', 'Registro creado!');
        }

        session()->flash('flash.type', 'success');

        return Redirect::route('tipo_prestamo', ['page' => $request->input('paginaActual')]);

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(TipoPrestamo $tipoPrestamo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(TipoPrestamo $tipoPrestamo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, TipoPrestamo $tipoPrestamo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  TipoPrestamo  $tipoPrestamo
     * @return Response
     */
    public function tables(TipoFrecuenciaPrestamo $TipoFrecuenciaPrestamo)
    {

        $TipoFrecuenciaPrestamoAll = $TipoFrecuenciaPrestamo->get();

        return compact('TipoFrecuenciaPrestamoAll');
    }

    public function destroy(TipoPrestamo $TipoPrestamo, $id, $page)
    {
        //
        $row = $TipoPrestamo->findOrFail($id);
        if (! $row->por_defecto) {
            $row->delete();
            session()->flash('flash.type', 'success');
            session()->flash('flash.message', 'Registro eliminado!');
        } else {
            session()->flash('flash.type', 'error');
            session()->flash('flash.message', 'No se puede eliminar el registro seleccionado');
        }

        return Redirect::route('tipo_prestamo', ['page' => $page]);
    }
}

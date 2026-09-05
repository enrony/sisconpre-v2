<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrupoUsuarioRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\GruposTrabajo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia;

class GruposTrabajoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, GruposTrabajo $GruposTrabajo)
    {
        \App::setLocale('es');

        return Inertia\Inertia::render(
            'gruposTrabajo',
            [
                'lista' => $GruposTrabajo::selectRaw("grupos_trabajos.*, lower( nombre ) as nombre_lower, date_format(grupos_trabajos.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(grupos_trabajos.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) {
                        $query->where('grupos_trabajos.nombre', 'LIKE', '%'.$term.'%');
                    })->where('grupos_trabajos.estatus', 1)
                    ->latest('grupos_trabajos.created_at')->paginate(50),
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
    public function store(GrupoUsuarioRequest $request)
    {
        if ($request->has('id') && $request->input('id') > 0) {
            GruposTrabajo::find($request->input('id'))->update($request->all());
            session()->flash('flash.message', 'Registro actualizado!');
        } else {

            GruposTrabajo::create($request->all());
            session()->flash('flash.message', 'Registro creado!');
        }

        session()->flash('flash.type', 'success');

        return Redirect::route('grupos_trabajo', ['page' => $request->input('paginaActual')]);
    }

    public function generateCode()
    {

        inicio:

        $code = Str::random(6);

        if (GruposTrabajo::where('code', $code)->exists()) {
            goto inicio;
        }

        return compact('code');
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(GruposTrabajo $gruposTrabajo, $id, $page)
    {
        $row = $gruposTrabajo->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('grupos_trabajo', ['page' => $page]);
    }

    public function tables(Country $Country)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();
        $CitiesAll = City::WhereCountriesActive($CountryAll->pluck('id')->toArray())->get();

        return compact('CountryAll', 'CitiesAll');
    }
}

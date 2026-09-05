<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFranquiciaRequest;
use App\Http\Requests\UpdateFranquiciaRequest;
use App\Models\Franquicia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class FranquiciaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Franquicia $BankAccountType)
    {
        $table = 'franquicias';

        return Inertia\Inertia::render(
            'Franquicias',
            [
                'lista' => $BankAccountType::selectRaw("{$table}.*, lower( description ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) use ($table) {
                        $query->where("{$table}.description", 'LIKE', '%'.$term.'%');
                    })->where("{$table}.estatus", 1)
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
     * @return Response
     */
    public function store(StoreFranquiciaRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            Franquicia::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = Franquicia::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('franquicias', ['page' => $request->input('paginaActual')]);
        } else {
            return [
                'success' => true,
                'id' => $id,
            ];
        }
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Franquicia $franquicia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Franquicia $franquicia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateFranquiciaRequest $request, Franquicia $franquicia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Franquicia  $franquicia
     * @return Response
     */
    public function destroy(Franquicia $Franquicia, $id, $page)
    {
        //
        $row = $Franquicia->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('franquicias', ['page' => $page]);
    }

    public function record(Franquicia $Franquicia, $id)
    {
        $record = $Franquicia->findOrFail($id);

        return compact('record');
    }

    public function tables(Franquicia $Franquicia)
    {
        $lista = $Franquicia->get();

        return compact('lista');
    }
}

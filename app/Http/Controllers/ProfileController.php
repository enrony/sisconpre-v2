<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Modules;
use App\Models\Profiles;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Profiles $Profiles)
    {
        $queryAll = $this->paginate($Profiles::lista($request));

        return Inertia\Inertia::render(
            'Profiles',
            [
                'lista' => $queryAll,
                'messages' => __('messages'),
            ]
        );
    }

    public function paginate($queryAll, $per_page = 15, $page = null, $options = [])
    {
        $collection = new Collection($queryAll);
        $page = Paginator::resolveCurrentPage() ?: 1;
        $per_page = 10;
        $currentPageResults = $collection->slice(($page - 1) * $per_page, $per_page)->values();

        return new LengthAwarePaginator($currentPageResults, count($collection), $per_page);
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
    public function store(ProfileRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            $Profile = Profiles::find($request->input('id')); // ->update($request->all());
            $Profile->fill($request->all());
            $Profile->save();

            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $Profile = Profiles::create($request->all());
            $id = $Profile->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        $ids = [];

        if (count($request->multiple_selection_actions) > 0) {
            $ids = collect($request->multiple_selection_actions)->pluck('pivot.id')->toArray();
        }

        // $Module->acciones()->sync( $ids );

        $existingIds = $Profile->acciones()->where('modules_actions.modules_id', $request->module_id)->pluck('modules_actions.id')->toArray();
        // Cambia el estado de los registros existentes a inactivo
        $Profile->acciones()->updateExistingPivot($existingIds, ['estatus' => false]);

        // Agrega o actualiza los registros con el estado activo
        $Profile->acciones()->syncWithoutDetaching($newIdsWithStatus = array_fill_keys($ids, ['estatus' => true, 'updated_at' => now()]));

        // Registramos las acciones

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('profile', ['page' => $request->input('paginaActual')]);
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
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    public function record(Profiles $Profiles, $id)
    {
        $record = $Profiles->findOrFail($id);

        return compact('record');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function tables()
    {

        $modules = Modules::with('acciones')->whereHas('actions')->get();

        return compact('modules');

    }

    public function destroy($id)
    {
        //
    }
}

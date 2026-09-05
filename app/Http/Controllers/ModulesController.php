<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModuleRequest;
use App\Http\Resources\ModuleResource;
use App\Http\Traits\permissionsTrait;
use App\Models\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Inertia;

class ModulesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    use permissionsTrait;

    public function index(Request $request, Modules $modules)
    {

        \App::setLocale('es');

        $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);

        return Inertia\Inertia::render(
            'Modules',
            [
                'messages' => __('messages'),
            ]
        );
    }

    public function record(Request $request, $id)
    {

        $Module = Modules::find($id);
        if ($Module) {
            return response()->json(new ModuleResource($Module));
        }

        return response()->json((new ModuleResource(null))->toArray(null));

    }

    public function records(Request $request, Modules $Modules)
    {

        $lista = $this->paginate($Modules::lista($request, $Modules));

        return compact('lista');

    }

    public function paginate($queryAll, $per_page = 15, $page = null, $options = [])
    {

        $queryAll = $queryAll->map(function ($item) {
            $item->estatus_selected = null;
            $item->support_image = [];
            $item->motivo = null;

            return $item;
        });

        $collection = new Collection($queryAll);
        $page = Paginator::resolveCurrentPage() ?: 1;
        $per_page = 100;
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

    public function modulesMenues(Request $request) {}

    public function modulesMenues2(Request $request)
    {

        $modules = Modules::getDataMeues();

        $isAdmin = $this->isUserAdmin($request);

        if (! $isAdmin) {
            $moulesUser = $this->modulesUserAccess($request);
            $modules = $this->selectModulesUserAccess($moulesUser, $modules);
        }

        $page = Paginator::resolveCurrentPage() ?: 1;
        $perPage = 100;
        $modules = new LengthAwarePaginator(
            $modules->forPage($page, $perPage),
            $modules->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return compact('modules');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreModuleRequest $request)
    {

        $Module = Modules::firstOrNew(['id' => $request->id]);

        $Module->fill($request->all());

        $Module->save();

        $ids = [];
        if (count($Module->multiple_selection) > 0) {
            $ids = collect($Module->multiple_selection)->pluck('id')->toArray();
        }
        $Module->parents()->sync($ids);

        $ids = [];

        if (count($Module->multiple_selection_actions) > 0) {
            $ids = collect($Module->multiple_selection_actions)->pluck('id')->toArray();
        }

        // $Module->acciones()->sync( $ids );

        $existingIds = $Module->acciones()->pluck('actions.id')->toArray();
        // Cambia el estado de los registros existentes a inactivo
        $Module->acciones()->updateExistingPivot($existingIds, ['estatus' => false]);

        // Agrega o actualiza los registros con el estado activo
        $Module->acciones()->syncWithoutDetaching($newIdsWithStatus = array_fill_keys($ids, ['estatus' => true, 'updated_at' => now()]));

        return [
            'success' => true,
            'message' => 'Registro modificado con éxito',
        ];

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Modules $modules)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Modules $modules)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Modules $modules)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Modules $modules)
    {
        //
    }

    public function esquema(Request $request)
    {
        $modules = Modules::with(['children'])->where('padre', true)->orderBy('order')->get();

        $isAdmin = $this->isUserAdmin($request);

        if (! $isAdmin) {
            $moulesUser = $this->modulesUserAccess($request);
            $modules = $this->selectModulesUserAccess($moulesUser, $modules);
        }

        $modules = ModuleResource::collection($modules);

        return compact('modules');
    }

    public function esquema_activos(Request $request)
    {
        $modules = Modules::with(['children'])->where('padre', true)->orderBy('order')->get();

        $modules = ModuleResource::collection($modules);

        return compact('modules');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;
use stdClass;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, City $City)
    {

        $queryAll = $this->paginate($City::lista($request));

        return Inertia\Inertia::render(
            'Cities',
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
        $per_page = 100;
        $currentPageResults = $collection->slice(($page - 1) * $per_page, $per_page)->values();

        return new LengthAwarePaginator($currentPageResults, count($collection), $per_page);
    }

    public function record(City $City, $id)
    {
        $record = $City->with(['department'])->findOrFail($id);

        return compact('record');
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
    public function store(CityRequest $request)
    {

        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            City::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = City::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('cities', ['page' => $request->input('paginaActual')]);
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
    public function show(City $city)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(City $city)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, City $city)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  City  $city
     * @return Response
     */
    public function destroy(City $City, $id, $page)
    {
        //
        $row = $City->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('cities', ['page' => $page]);
    }

    public function tables(Country $Country, Department $department)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();
        $DepartmentAll = $department->lista(new stdClass);

        return compact('CountryAll', 'DepartmentAll');
    }
}

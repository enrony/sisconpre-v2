<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Country;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Department $Department)
    {

        $queryAll = $this->paginate($Department::lista($request));

        return Inertia\Inertia::render(
            'Departments',
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
     * @param  StoreDepartmentRequest  $request
     * @return Response
     */
    public function store(DepartmentRequest $request)
    {

        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            Department::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = Department::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('departamentos', ['page' => $request->input('paginaActual')]);
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
    public function show(Department $department)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Department $department)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Department  $department
     * @return Response
     */
    public function destroy(Department $Department, $id, $page)
    {
        //
        $row = $Department->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('departamentos', ['page' => $page]);
    }

    public function tables(Country $Country, Department $department)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();

        return compact('CountryAll');
    }

    public function record(Department $department, $id)
    {
        $record = $department->with(['country'])->findOrFail($id);

        return compact('record');
    }
}

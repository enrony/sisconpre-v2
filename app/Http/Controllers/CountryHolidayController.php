<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryHolidayRequest;
use App\Http\Requests\UpdateCountryHolidayRequest;
use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class CountryHolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, CountryHoliday $CountryHoliday)
    {

        $queryAll = $this->paginate($CountryHoliday->lista($request));

        return Inertia\Inertia::render(
            'Festivos',
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

    public function tables(Country $Country, Department $department)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();

        return compact('CountryAll');
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
    public function store(StoreCountryHolidayRequest $request)
    {

        $id = 0;

        $request['year'] = date('Y', strtotime($request->input('date')));

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            CountryHoliday::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = CountryHoliday::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('festivos', ['page' => $request->input('paginaActual')]);
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
    public function show(CountryHoliday $countryHoliday)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(CountryHoliday $countryHoliday)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateCountryHolidayRequest $request, CountryHoliday $countryHoliday)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  CountryHoliday  $countryHoliday
     * @return Response
     */
    public function destroy(CountryHoliday $CountryHoliday, $id, $page)
    {
        //
        $row = $CountryHoliday->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('festivos', ['page' => $page]);
    }

    public function record(CountryHoliday $CountryHoliday, $id)
    {
        $record = $CountryHoliday->with(['country'])->findOrFail($id);

        return compact('record');
    }
}

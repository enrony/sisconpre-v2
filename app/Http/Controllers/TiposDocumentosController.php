<?php

namespace App\Http\Controllers;

use App\Http\Requests\TipoDocumentosRequest;
use App\Models\Country;
use App\Models\tiposDocumentos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class TiposDocumentosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, tiposDocumentos $tiposDocumentos)
    {

        $queryAll = $this->paginate($tiposDocumentos::listaDocumentos($request));

        return Inertia\Inertia::render(
            'TipoDocumentos',
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

    public function paginate_resp($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
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
    public function store(TipoDocumentosRequest $request)
    {

        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            tiposDocumentos::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = tiposDocumentos::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('tipo_documentos', ['page' => $request->input('paginaActual')]);
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
    public function show(tiposDocumentos $tiposDocumentos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(tiposDocumentos $tiposDocumentos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, tiposDocumentos $tiposDocumentos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(tiposDocumentos $tiposDocumentos, $id, $page)
    {
        //
        $row = $tiposDocumentos->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('tipo_documentos', ['page' => $page]);
    }

    public function tables(Country $Country)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();

        return compact('CountryAll');
    }

    public function record(tiposDocumentos $tiposDocumentos, $id)
    {
        $record = $tiposDocumentos->findOrFail($id);

        return compact('record');
    }
}

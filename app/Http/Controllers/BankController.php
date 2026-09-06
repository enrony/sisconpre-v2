<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankRequest;
use App\Http\Requests\UpdateBankRequest;
use App\Models\Bank;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Bank $Bank)
    {
        $table = 'banks';

        return Inertia\Inertia::render(
            'Bank',
            [
                'lista' => $Bank::selectRaw("{$table}.id, {$table}.code, {$table}.description, {$table}.country_id, {$table}.estatus, {$table}.created_at, {$table}.updated_at, lower( description ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
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
    public function store(StoreBankRequest $request)
    {

        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            Bank::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = Bank::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('banks', ['page' => $request->input('paginaActual')]);
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
    public function show(Bank $bank)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Bank $bank)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateBankRequest $request, Bank $bank)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Bank  $bank
     * @return Response
     */
    public function destroy(Bank $Bank, $id, $page)
    {
        //
        $row = $Bank->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('banks', ['page' => $page]);
    }

    public function record(Bank $bank, $id)
    {
        $record = $bank->findOrFail($id);

        return compact('record');
    }

    public function tables(Country $Country, $country_id = null)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();

        if ($country_id) {
            $Bank = Bank::where('country_id', $country_id)->get();
        } else {
            $Bank = Bank::get();
        }

        return compact('CountryAll', 'Bank');
    }
}

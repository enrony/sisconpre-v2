<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankAccountTypeRequest;
use App\Http\Requests\UpdateBankAccountTypeRequest;
use App\Models\BankAccountType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class BankAccountTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, BankAccountType $BankAccountType)
    {
        $table = 'bank_account_types';

        return Inertia\Inertia::render(
            'BankAccountType',
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
    public function store(StoreBankAccountTypeRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            BankAccountType::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = BankAccountType::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('bank_account_types', ['page' => $request->input('paginaActual')]);
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
    public function show(BankAccountType $bankAccountType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(BankAccountType $bankAccountType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateBankAccountTypeRequest $request, BankAccountType $bankAccountType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  BankAccountType  $bankAccountType
     * @return Response
     */
    public function destroy(BankAccountType $BankAccountType, $id, $page)
    {
        //
        $row = $BankAccountType->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('bank_account_types', ['page' => $page]);
    }

    public function record(BankAccountType $BankAccountType, $id)
    {
        $record = $BankAccountType->findOrFail($id);

        return compact('record');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypePaymentRecordRequest;
use App\Http\Requests\UpdateTypePaymentRecordRequest;
use App\Models\TypePaymentRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class TypePaymentRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, TypePaymentRecord $TypePaymentRecord)
    {
        $table = 'type_payment_records';

        return Inertia\Inertia::render(
            'typePaymentRecord',
            [
                'lista' => $TypePaymentRecord::selectRaw("{$table}.*, lower( description ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
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
    public function store(StoreTypePaymentRecordRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            TypePaymentRecord::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = TypePaymentRecord::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('type_payment_record', ['page' => $request->input('paginaActual')]);
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
    public function show(TypePaymentRecord $typePaymentRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(TypePaymentRecord $typePaymentRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateTypePaymentRecordRequest $request, TypePaymentRecord $typePaymentRecord)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  TypePaymentRecord  $typePaymentRecord
     * @return Response
     */
    public function destroy(TypePaymentRecord $TypePaymentRecord, $id, $page)
    {
        //
        $row = $TypePaymentRecord->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('type_payment_record', ['page' => $page]);
    }

    public function record(TypePaymentRecord $TypePaymentRecord, $id)
    {
        $record = $TypePaymentRecord->findOrFail($id);

        return compact('record');
    }

    public function tables(TypePaymentRecord $TypePaymentRecord)
    {
        $lista = $TypePaymentRecord->get();

        return compact('lista');
    }
}

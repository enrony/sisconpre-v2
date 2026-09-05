<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentFormRequest;
use App\Http\Requests\UpdatePaymentFormRequest;
use App\Models\PaymentForm;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class PaymentFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, PaymentForm $PaymentForm)
    {
        $table = 'payment_forms';

        return Inertia\Inertia::render(
            'PaymentForm',
            [
                'lista' => $PaymentForm::selectRaw("{$table}.*, lower( description ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) use ($table) {
                        $query->where("{$table}.description", 'LIKE', '%'.$term.'%');
                    })->where("{$table}.estatus", 1)
                // ->with(['frecuencias'])
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
    public function store(StorePaymentFormRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            PaymentForm::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = PaymentForm::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('payment_forms', ['page' => $request->input('paginaActual')]);
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
    public function show(PaymentForm $paymentForm)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PaymentForm $paymentForm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePaymentFormRequest $request, PaymentForm $paymentForm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  PaymentForm  $paymentForm
     * @return Response
     */
    public function destroy(PaymentForm $PaymentForm, $id, $page)
    {
        //
        $row = $PaymentForm->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('payment_forms', ['page' => $page]);
    }

    public function record(PaymentForm $PaymentForm, $id)
    {
        $record = $PaymentForm->findOrFail($id);

        return compact('record');
    }
}

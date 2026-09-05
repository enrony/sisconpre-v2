<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, PaymentMethod $PaymentMethod)
    {
        $table = 'payment_methods';

        return Inertia\Inertia::render(
            'PaymentMethod',
            [
                'lista' => $PaymentMethod::selectRaw("{$table}.*, lower( description ) as nombre_lower, date_format({$table}.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format({$table}.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
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
    public function store(StorePaymentMethodRequest $request)
    {
        $id = 0;

        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            PaymentMethod::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {
            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = PaymentMethod::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }

        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('payment_methods', ['page' => $request->input('paginaActual')]);
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
    public function show(PaymentMethod $paymentMethod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  PaymentMethod  $paymentMethod
     * @return Response
     */
    public function destroy(PaymentMethod $PaymentMethod, $id, $page)
    {
        //
        $row = $PaymentMethod->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('payment_methods', ['page' => $page]);
    }

    public function record(PaymentMethod $PaymentMethod, $id)
    {
        $record = $PaymentMethod->findOrFail($id);

        return compact('record');
    }

    public function tables(PaymentMethod $PaymentMethod)
    {

        $PaymentMethod = $PaymentMethod->get();

        return compact('PaymentMethod');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentReportsMovementsEstatuRequest;
use App\Http\Requests\UpdatePaymentReportsMovementsEstatuRequest;
use App\Models\PaymentReportsMovementsEstatu;
use Illuminate\Http\Response;

class PaymentReportsMovementsEstatuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
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
    public function store(StorePaymentReportsMovementsEstatuRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(PaymentReportsMovementsEstatu $paymentReportsMovementsEstatu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PaymentReportsMovementsEstatu $paymentReportsMovementsEstatu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePaymentReportsMovementsEstatuRequest $request, PaymentReportsMovementsEstatu $paymentReportsMovementsEstatu)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(PaymentReportsMovementsEstatu $paymentReportsMovementsEstatu)
    {
        //
    }

    public function tables(PaymentReportsMovementsEstatu $PaymentReportsMovementsEstatu)
    {

        $PaymentReportsMovementsEstatu = $PaymentReportsMovementsEstatu->get();

        return compact('PaymentReportsMovementsEstatu');
    }
}

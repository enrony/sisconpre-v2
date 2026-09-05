<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentReportsMovementRequest;
use App\Http\Requests\UpdatePaymentReportsMovementRequest;
use App\Models\PaymentReportsMovement;
use Illuminate\Http\Response;

class PaymentReportsMovementController extends Controller
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
    public function store(StorePaymentReportsMovementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(PaymentReportsMovement $paymentReportsMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PaymentReportsMovement $paymentReportsMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePaymentReportsMovementRequest $request, PaymentReportsMovement $paymentReportsMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(PaymentReportsMovement $paymentReportsMovement)
    {
        //
    }
}

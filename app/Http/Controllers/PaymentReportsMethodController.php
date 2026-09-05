<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorepaymentReportsMethodRequest;
use App\Http\Requests\UpdatepaymentReportsMethodRequest;
use App\Models\paymentReportsMethod;
use Illuminate\Http\Response;

class PaymentReportsMethodController extends Controller
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
    public function store(StorepaymentReportsMethodRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(paymentReportsMethod $paymentReportsMethod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(paymentReportsMethod $paymentReportsMethod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatepaymentReportsMethodRequest $request, paymentReportsMethod $paymentReportsMethod)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(paymentReportsMethod $paymentReportsMethod)
    {
        //
    }
}

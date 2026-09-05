<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelectedPaymentReportRequest;
use App\Http\Requests\UpdateSelectedPaymentReportRequest;
use App\Models\SelectedPaymentReport;
use Illuminate\Http\Response;

class SelectedPaymentReportController extends Controller
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
    public function store(StoreSelectedPaymentReportRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SelectedPaymentReport $selectedPaymentReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SelectedPaymentReport $selectedPaymentReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSelectedPaymentReportRequest $request, SelectedPaymentReport $selectedPaymentReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SelectedPaymentReport $selectedPaymentReport)
    {
        //
    }
}

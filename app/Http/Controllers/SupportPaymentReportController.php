<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportPaymentReportRequest;
use App\Http\Requests\UpdateSupportPaymentReportRequest;
use App\Models\SupportPaymentReport;
use Illuminate\Http\Response;

class SupportPaymentReportController extends Controller
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
    public function store(StoreSupportPaymentReportRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SupportPaymentReport $supportPaymentReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SupportPaymentReport $supportPaymentReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSupportPaymentReportRequest $request, SupportPaymentReport $supportPaymentReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SupportPaymentReport $supportPaymentReport)
    {
        //
    }
}

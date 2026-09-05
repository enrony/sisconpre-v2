<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSummaryCustomerMovementRequest;
use App\Http\Requests\UpdateSummaryCustomerMovementRequest;
use App\Models\SummaryCustomerMovement;
use Illuminate\Http\Response;

class SummaryCustomerMovementController extends Controller
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
    public function store(StoreSummaryCustomerMovementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SummaryCustomerMovement $summaryCustomerMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SummaryCustomerMovement $summaryCustomerMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSummaryCustomerMovementRequest $request, SummaryCustomerMovement $summaryCustomerMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SummaryCustomerMovement $summaryCustomerMovement)
    {
        //
    }
}

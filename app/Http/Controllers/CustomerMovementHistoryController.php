<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerMovementHistoryRequest;
use App\Http\Requests\UpdateCustomerMovementHistoryRequest;
use App\Models\CustomerMovementHistory;
use Illuminate\Http\Response;

class CustomerMovementHistoryController extends Controller
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
    public function store(StoreCustomerMovementHistoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(CustomerMovementHistory $customerMovementHistory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(CustomerMovementHistory $customerMovementHistory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateCustomerMovementHistoryRequest $request, CustomerMovementHistory $customerMovementHistory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(CustomerMovementHistory $customerMovementHistory)
    {
        //
    }
}

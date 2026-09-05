<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypesMovementRequest;
use App\Http\Requests\UpdateTypesMovementRequest;
use App\Models\TypesMovement;
use Illuminate\Http\Response;

class TypesMovementController extends Controller
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
    public function store(StoreTypesMovementRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(TypesMovement $typesMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(TypesMovement $typesMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateTypesMovementRequest $request, TypesMovement $typesMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(TypesMovement $typesMovement)
    {
        //
    }
}

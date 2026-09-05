<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrestamosEstatuRequest;
use App\Http\Requests\UpdatePrestamosEstatuRequest;
use App\Models\PrestamosEstatu;
use Illuminate\Http\Response;

class PrestamosEstatuController extends Controller
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
    public function store(StorePrestamosEstatuRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(PrestamosEstatu $prestamosEstatu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(PrestamosEstatu $prestamosEstatu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdatePrestamosEstatuRequest $request, PrestamosEstatu $prestamosEstatu)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(PrestamosEstatu $prestamosEstatu)
    {
        //
    }
}

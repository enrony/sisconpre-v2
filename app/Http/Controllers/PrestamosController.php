<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Prestamos;
use App\Models\PrestamosDias;
use App\Models\PrestamosEstatu;
use App\Models\TipoPrestamo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Inertia;

class PrestamosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, Prestamos $prestamos)
    {
        \App::setLocale('es');

        return Inertia\Inertia::render('Prestamos',
            [
                'messages' => __('messages'),
            ]);
    }

    public function recordsEstados()
    {
        $estados = PrestamosEstatu::where('estatus', 1)->get();

        return compact('estados');
    }

    public function records(Request $request, Prestamos $Prestamos)
    {

        $lista = $this->paginate($Prestamos::lista($request, $Prestamos), 10);

        return compact('lista');

    }

    public function index_old(Request $request, Prestamos $prestamos)
    {
        \App::setLocale('es');

        $lista = $prestamos->with(['prestamos_dias'])->select('*', 'created_at as created')->get();

        return Inertia\Inertia::render('Prestamos',
            [
                'lista' => $this->paginate($lista, 10),
                'messages' => __('messages'),
            ]);
    }

    public function transformData($queryAll)
    {
        return $queryAll = $queryAll->map(function ($item) {
            // $item->pendientesPago2 = true;
            $item->prestamos_dias = $item->prestamos_dias->map(function ($pdias) {
                $pdias->pendientesPago2 = false;
                if ($pdias->pendientesPago && $pdias->pendientesPago->paymentReport && $pdias->pendientesPago->paymentReport->payment_reports_movement_first->estatus_description->finish_estatus == 0) {
                    $pdias->pendientesPago2 = true;
                }

                return $pdias;
            });

            return $item;
        });
    }

    public function paginate($queryAll, $per_page2 = 1000, $page = null, $options = [])
    {

        $collection = new Collection($queryAll);
        $page = Paginator::resolveCurrentPage() ?: 1;
        $per_page = $per_page2;
        $currentPageResults = $collection->slice(($page - 1) * $per_page, $per_page)->values();

        return new LengthAwarePaginator($currentPageResults, count($collection), $per_page);
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
    public function store(Request $request)
    {

        $data = $request->all();
        $data['cliente'] = $data['clienteSelected'];
        $data['form'] = null;

        $GruposTrabajoUser = $this->obtenerGrupoTrabajo();
        $data['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
        $Prestamos = Prestamos::create($data);

        // $PrestamosDias = new PrestamosDias();
        // $PrestamosDiasFillable = $PrestamosDias->getFillable();

        $cuotas = [];

        collect($data['list_pays'])->map(function ($lista) use (&$cuotas) {

            // if($data['apply_surcharge'] == 'true'){
            //     $lista['apply_surcharge'] = true;
            //     $lista['surcharge'] = $data['surcharge'];
            //     $lista['days_apply_surcharge'] = $data['days_apply_surcharge'];
            // }

            $PrestamosDias = new PrestamosDias;
            $PrestamosDias->fill($lista);
            array_push($cuotas, $PrestamosDias);
        });

        $Prestamos->prestamos_dias()->saveMany($cuotas);

        session()->flash('flash.message', 'Registro creado!');

        session()->flash('flash.type', 'success');

        return Redirect::route('prestamos', ['page' => $request->input('paginaActual')]);

    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Prestamos $prestamos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Prestamos $prestamos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Prestamos $prestamos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Prestamos $prestamos)
    {
        //
    }

    public function tables(TipoPrestamo $TipoPrestamo)
    {

        $TipoPrestamo = $TipoPrestamo->get();
        $CountryHoliday = CountryHoliday::CriterioYear()->get();
        $CountryAll = Country::WhereAllCountriesAssigned()->get();

        return compact('TipoPrestamo', 'CountryHoliday', 'CountryAll');
    }

    public function obtenerPrestamosActivos(Prestamos $prestamos, $cliente)
    {
        return $this->transformData($prestamos->with(['prestamos_dias' => function ($prestamos) {
            $prestamos->with(['pendientesPago']);
        }])->select('*', 'created_at as created')->PrestamoActivo($cliente)->get());
    }

    /**
     * Pausa / reanuda la aplicación del recargo por mora del préstamo.
     * `pause_surcharge` lo respeta el comando `prestamos:aplicar-recargos`.
     */
    public function togglePausaRecargo(Prestamos $prestamo)
    {
        $prestamo->pause_surcharge = ! $prestamo->pause_surcharge;
        $prestamo->save();

        return [
            'success' => true,
            'pause_surcharge' => (bool) $prestamo->pause_surcharge,
            'message' => $prestamo->pause_surcharge
                ? 'Recargo por mora pausado'
                : 'Recargo por mora reanudado',
        ];
    }
}

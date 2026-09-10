<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CountryHoliday;
use App\Models\Prestamos;
use App\Models\PrestamosDias;
use App\Models\PrestamosEstatu;
use App\Models\TipoPrestamo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /**
     * @return array{lista: LengthAwarePaginator<int, array<string, mixed>>}
     */
    public function records(Request $request, Prestamos $Prestamos): array
    {
        $lista = Prestamos::lista($request, $Prestamos)
            ->paginate(10)
            ->through(fn (Prestamos $r): array => $this->toRow($r));

        return compact('lista');
    }

    /**
     * Fila del listado: exactamente lo que consume `PrestamosTable.vue`
     * (incluye `prestamos_dias` para el detalle expandible, ya sólo de la página).
     *
     * @return array<string, mixed>
     */
    private function toRow(Prestamos $r): array
    {
        return [
            'id' => $r->id,
            'cliente_id' => $r->cliente_id,
            'cliente' => [
                'nombre' => $r->cliente_nombre,
                'apellido' => $r->cliente_apellido,
                'documento' => $r->cliente_documento,
            ],
            'created' => $r->created,
            'date_first_pay' => $r->date_first_pay,
            'date_last_pay' => $r->date_last_pay,
            'monto_prestamo' => $r->monto_prestamo,
            'tasa' => $r->tasa,
            'utilidad' => $r->utilidad,
            'total' => $r->total,
            'estatus' => $r->estatus,
            'pause_surcharge' => (bool) $r->pause_surcharge,
            'p_estatus' => $r->p_estatus ? [
                'id' => $r->p_estatus->id,
                'type_tag' => $r->p_estatus->type_tag,
            ] : null,
            'prestamos_dias' => $r->prestamos_dias->map(fn (PrestamosDias $d): array => [
                'id' => $d->id,
                'date' => $d->date,
                'sigla' => $d->sigla,
                'cuota' => $d->cuota,
                'apply' => (bool) $d->apply,
                'pagado' => (bool) $d->pagado,
                'dom' => (bool) $d->dom,
                'festivo' => (bool) $d->festivo,
            ])->all(),
        ];
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\GrupoUsuarioRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\GruposTrabajo;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia;

class GruposTrabajoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, GruposTrabajo $GruposTrabajo)
    {
        \App::setLocale('es');

        return Inertia\Inertia::render(
            'gruposTrabajo',
            [
                'lista' => $GruposTrabajo::selectRaw("grupos_trabajos.*, lower( nombre ) as nombre_lower, date_format(grupos_trabajos.created_at, '%Y-%m-%d %H:%i:%s') as created, date_format(grupos_trabajos.updated_at, '%Y-%m-%d %H:%i:%s') as updated")
                    ->when($request->term, function ($query, $term) {
                        $query->where('grupos_trabajos.nombre', 'LIKE', '%'.$term.'%');
                    })->where('grupos_trabajos.estatus', 1)
                    ->latest('grupos_trabajos.created_at')->paginate(50),
                'messages' => __('messages'),
            ]
        );
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
     * @param  Request  $request
     * @return Response
     */
    public function store(GrupoUsuarioRequest $request)
    {
        if ($request->has('id') && $request->input('id') > 0) {
            $data = $request->all();
            $data['code'] = $this->resolverCodigo($data['code'] ?? null, (int) $request->input('id'));
            GruposTrabajo::find($request->input('id'))->update($data);
            session()->flash('flash.message', 'Registro actualizado!');
        } else {
            $data = $request->all();
            $data['code'] = $this->resolverCodigo($data['code'] ?? null);
            GruposTrabajo::create($data);
            session()->flash('flash.message', 'Registro creado!');
        }

        session()->flash('flash.type', 'success');

        return Redirect::route('grupos_trabajo', ['page' => $request->input('paginaActual')]);
    }

    /**
     * Código de vinculación de un usuario nuevo a este grupo de trabajo (se
     * ingresa en el registro público, `/register`, y hereda el país del
     * grupo). El frontend lo pide con `generateCode()` al abrir el modal (y
     * cada vez que se toque el ícono de refresh) y lo muestra en pantalla
     * para que quien crea/edita el grupo pueda copiarlo y compartirlo — por
     * eso, a diferencia de otros campos, si el formulario ya trae un código
     * válido y libre se respeta tal cual (es el que la persona vio y va a
     * compartir); solo se genera uno nuevo en el servidor si falta o si en el
     * instante de guardar ya lo tomó otro grupo (carrera improbable, no motivo
     * para bloquear el guardado).
     */
    private function resolverCodigo(?string $code, ?int $excludeId = null): string
    {
        $code = $code ? Str::upper(trim($code)) : null;

        if ($code === null) {
            return $this->generarCodigoUnico();
        }

        $yaExiste = GruposTrabajo::where('code', $code)
            ->when($excludeId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();

        return $yaExiste ? $this->generarCodigoUnico() : $code;
    }

    /**
     * Código único de 6 caracteres, para el ícono de "generar/regenerar" del
     * formulario de grupo de trabajo.
     *
     * @return array{code: string}
     */
    public function generateCode(): array
    {
        return ['code' => $this->generarCodigoUnico()];
    }

    private function generarCodigoUnico(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GruposTrabajo::where('code', $code)->exists());

        return $code;
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, GruposTrabajo $gruposTrabajo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(GruposTrabajo $gruposTrabajo, $id, $page)
    {
        $row = $gruposTrabajo->findOrFail($id);
        $row->delete();
        session()->flash('flash.type', 'success');
        session()->flash('flash.message', 'Registro eliminado!');

        return Redirect::route('grupos_trabajo', ['page' => $page]);
    }

    public function tables(Country $Country)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();
        $CitiesAll = City::WhereCountriesActive($CountryAll->pluck('id')->toArray())->get();

        return compact('CountryAll', 'CitiesAll');
    }
}

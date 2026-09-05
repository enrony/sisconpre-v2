<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Clientes;
use App\Models\Country;
use App\Models\Department;
use App\Models\Modules;
use App\Models\Prestamos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia;
use stdClass;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function listaClientes(Request $request, Clientes $clientes)
    {
        //
        // return $clientes::get();
        // return Inertia\Inertia::render('Clientes');

        // dd($request->user()->id);

        $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request); // grupo de trabajo del usuario
        // dd( $GruposTrabajoUser->idgrupo_trabajo );
        // dd(Modules::with(['actions'])->get());

        $su = $this->isSuperUsuario($request->user()->id); // true si es super usuario

        return Inertia\Inertia::render(
            'Clientes',
            [
                'lista' => $clientes::selectRaw('clientes.id, clientes.documento, clientes.nombre, clientes.nombre_segundo, clientes.apellido, clientes.apellido_segundo, clientes.telefono, clientes.direccion, clientes.grupos_trabajos_user_id, clientes.idtipo_documento, clientes.email, clientes.city_id, clientes.email_verified_at, clientes.created_at as created_at, td.sigla')
                    ->when($request->term, function ($query, $term) {
                        $query->where('clientes.nombre', 'LIKE', '%'.$term.'%');
                        $query->orWhere('apellido', 'LIKE', '%'.$term.'%');
                        $query->orWhere('documento', 'LIKE', '%'.$term.'%');
                    })->where('clientes.estatus', 1)
                    ->join('grupos_trabajos_users', function ($j) use ($GruposTrabajoUser, $su) {
                        $j->on('grupos_trabajos_users.id', '=', 'clientes.grupos_trabajos_user_id');
                        if (! $su) {
                            $j->where('grupos_trabajos_users.idgrupo_trabajo', $GruposTrabajoUser->idgrupo_trabajo);
                        }
                    })
                    ->join('tipos_documentos as td', function ($join) {
                        $join->on('td.id', '=', 'clientes.idtipo_documento');
                    })
                    ->latest('clientes.created_at')->paginate(2),
                'messages' => __('messages'),
            ]
        );
    }

    public function listaClientesJson(Clientes $clientes)
    {

        $GruposTrabajoUser = $this->obtenerGrupoTrabajo();
        $su = $this->isSuperUsuario(auth()->user()->id);

        $clien = $clientes::selectRaw('clientes.id, clientes.documento, clientes.nombre, clientes.nombre_segundo, clientes.apellido, clientes.apellido_segundo, clientes.telefono, clientes.direccion, clientes.grupos_trabajos_user_id, clientes.idtipo_documento, clientes.email, clientes.city_id, clientes.email_verified_at, clientes.created_at as created_at, td.sigla')
            ->join('tipos_documentos as td', function ($join) {
                $join->on('td.id', '=', 'clientes.idtipo_documento');
            })
            ->join('grupos_trabajos_users', function ($j) use ($GruposTrabajoUser, $su) {
                $j->on('grupos_trabajos_users.id', '=', 'clientes.grupos_trabajos_user_id');
                if (! $su) {
                    $j->where('grupos_trabajos_users.idgrupo_trabajo', $GruposTrabajoUser->idgrupo_trabajo);
                }
            })->orderBy('clientes.nombre')->get();

        return compact('clien');
    }

    public function listaClientesJsonBasic(Request $request, Clientes $clientes)
    {
        $gruposTrabajoUser = $this->obtenerGrupoTrabajo();
        $esSuperUsuario = $this->isSuperUsuario(auth()->id());

        $clien = $clientes::selectRaw('clientes.id, clientes.documento, clientes.nombre, clientes.nombre_segundo, clientes.apellido, clientes.apellido_segundo, clientes.telefono, clientes.direccion, clientes.grupos_trabajos_user_id, clientes.idtipo_documento, clientes.email, clientes.city_id, clientes.email_verified_at, clientes.created_at as created_at, td.sigla')
            ->join('tipos_documentos as td', 'td.id', '=', 'clientes.idtipo_documento')
            ->join('grupos_trabajos_users as gtu', function ($join) use ($gruposTrabajoUser, $esSuperUsuario) {
                $join->on('gtu.id', '=', 'clientes.grupos_trabajos_user_id');
                if (! $esSuperUsuario) {
                    $join->where('gtu.idgrupo_trabajo', $gruposTrabajoUser->idgrupo_trabajo);
                }
            })
            ->when($request->filled('cliente'), function ($query) use ($request) {
                $cliente = '%'.$request->input('cliente').'%';
                $query->where(function ($q) use ($cliente) {
                    $q->where('clientes.nombre', 'like', $cliente)
                        //   ->orWhere('clientes.nombre_segundo', 'like', $cliente)
                        ->orWhere('clientes.apellido', 'like', $cliente);
                    //   ->orWhere('clientes.apellido_segundo', 'like', $cliente)
                });
            })
            ->orderBy('clientes.nombre')
            ->get()
            ->map(fn ($cli) => collect($cli)->except(['informes_pandientes_activos', 'pretamos']));

        return compact('clien');
    }

    public function listaClientes2(Clientes $clientes)
    {
        //
        return $clientes::get();
        // return Inertia\Inertia::render('Clientes');

        return Inertia\Inertia::render(
            'Clientes',
            [
                'lista' => $clientes->latest()->get(),
            ]
        );
    }

    public function actualizaCliente(Request $request, Clientes $clientes)
    {

        // Request::validate([
        //     'first_name' => ['required', 'max:50'],
        //     'last_name' => ['required', 'max:50'],
        //     'email' => ['required', 'max:50', 'email'],
        // ]);
        //  dd($request->all());
        // Validator::make($request->all(), [
        //     'idtipo_documento' => ['required'],
        //     'nombre' => ['required'],
        //     'direccion' => ['required'],
        //     'documento' => ['required'],
        //     'telefono' => ['required'],
        // ])->validate();

        // $this->validate($request, [
        //     'names'=>'required|max:100|min:5|regex:/^([^0-9]*)$/',
        //     'surnames'=>'required|max:100|regex:/^([^0-9]*)$/',
        //     'address'=>'required|max:180',
        //     'cell_phone'=>'required|numeric|digits_between:1,10|without_spaces',
        //     'country'=>'required',
        //     'id_category'=>'required',
        // ]);

        //     $rules = [
        //     'name' => 'required',
        //     'email' => 'required|email',
        //     'message' => 'required|max:250',
        // ];

        // $customMessages = [
        //     'required' => 'The :attribute field is required.'
        // ];

        // $this->validate($request, $rules, $customMessages);

        $this->validate(
            $request,
            [
                'idtipo_documento' => 'required|integer|min:1',
                'nombre' => 'required|max:100',
                'nombre_segundo' => 'max:50',
                'apellido' => 'max:50',
                'apellido_segundo' => 'max:50',
                'documento' => 'required|max:20',
                'telefono' => 'required|max:15',
                'direccion' => 'required:max:200',
                'email' => 'required|email|max:100',
                'city_id' => 'required',
            ]
            // ,
            //    [
            //     'idtipo_documento.required'=> 'Requerido', // custom message
            //     'idtipo_documento.min'=> 'Requerido', // custom message
            //     'nombre.required'=> 'Requerido', // custom message
            //     'documento.required'=> 'Requerido' // custom message
            //    ]
        );

        $id = 0;

        // $dCliente = $clientes::find($request->id);
        if ($request->has('id') && $request->input('id') > 0) {
            $id = $request->input('id');
            Clientes::find($request->input('id'))->update($request->all());
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro actualizado!');
            }
        } else {

            $GruposTrabajoUser = $this->obtenerGrupoTrabajo($request);
            $request['grupos_trabajos_user_id'] = $GruposTrabajoUser->id;
            $new = Clientes::create($request->all());
            $id = $new->id;
            if (! $request->otherForm) {
                session()->flash('flash.message', 'Registro creado!');
            }
        }
        if (! $request->otherForm) {
            session()->flash('flash.type', 'success');

            return Redirect::route('clientes', ['page' => $request->input('paginaActual')]);
        } else {
            return [
                'success' => true,
                'id' => $id,
            ];
        }
    }

    public function consultaPrestamos(Request $request, Clientes $clientes, $id_cliente)
    {
        $clientes = $clientes->find($id_cliente);
        // dd($clientes);

        $prestamos = Prestamos::Cliente($id_cliente);
        dd($prestamos);

        return compact('exito');
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Clientes $clientes)
    {
        //
    }

    public function record(Clientes $clientes, $id)
    {
        $record = $clientes->with(['city'])->findOrFail($id);

        return compact('record');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Clientes $clientes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Clientes $clientes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Clientes $clientes)
    {
        //
    }

    public function tables(Country $Country)
    {

        $CountryAll = $Country->WhereAllCountriesAssigned()->get();
        $DepartmentAll = Department::lista(new stdClass);
        $CitiesAll = City::WhereCountriesActive($CountryAll->pluck('id')->toArray())->get();

        return compact('CountryAll', 'DepartmentAll', 'CitiesAll');
    }
}

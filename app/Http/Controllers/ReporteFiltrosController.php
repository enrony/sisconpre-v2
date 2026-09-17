<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\PaymentReportsMovementsEstatu;
use App\Models\PrestamosEstatu;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Datos de filtro compartidos por todos los módulos de "Reportes" (país →
 * ciudad → grupo de trabajo → responsable, en cascada). Vive acá y no en cada
 * ReporteXController para no reescribir la cascada en cada reporte nuevo.
 *
 * A propósito NO reusa endpoints de otros módulos (p. ej.
 * `/prestamos/recordsEstados`) para los catálogos: esos están gateados por el
 * permiso de SU módulo (`prestamos.listar`), y alguien con solo
 * `reporte_prestamos.listar` (sin acceso a `/prestamos`) se quedaría sin
 * poder cargar el filtro.
 */
class ReporteFiltrosController extends Controller
{
    /**
     * IDs de país a los que puede acceder el usuario actual: si tiene país
     * activo fijo, solo ese; si no (superusuario), todos los activos.
     *
     * @return array<int, string>
     */
    private function paisesPermitidos(): array
    {
        $paisActivo = static::obtenerPaisActivo();

        return $paisActivo
            ? [$paisActivo]
            : Country::where('estatus', 1)->pluck('id')->all();
    }

    /**
     * Países dentro del alcance del usuario actual.
     *
     * @return array<string, mixed>
     */
    public function paises(): array
    {
        $CountryAll = Country::whereIn('id', $this->paisesPermitidos())->where('estatus', 1)->get(['id', 'Name']);

        return compact('CountryAll');
    }

    /**
     * Ciudades de un país. Sin `pais`, devuelve vacío a propósito (no tiene
     * sentido ofrecer todas las ciudades de todos los países en un filtro).
     *
     * @return array<string, mixed>
     */
    public function ciudades(Request $request): array
    {
        $paisActivo = static::obtenerPaisActivo();
        $pais = $paisActivo ?: $request->query('pais');

        if (! $pais || ! in_array($pais, $this->paisesPermitidos(), true)) {
            return ['CitiesAll' => []];
        }

        $CitiesAll = City::WhereCountriesActive([$pais])->get();

        return compact('CitiesAll');
    }

    /**
     * Grupos de trabajo. Sin `pais`, todos los del alcance del usuario (así
     * lo pidió el negocio: el combo arranca mostrando todos). Con `pais`,
     * solo los de ese país.
     *
     * @return array<string, mixed>
     */
    public function gruposTrabajo(Request $request): array
    {
        $paisActivo = static::obtenerPaisActivo();
        $pais = $paisActivo ?: $request->query('pais');

        $paisesPermitidos = $this->paisesPermitidos();
        $paisesFiltro = ($pais && in_array($pais, $paisesPermitidos, true)) ? [$pais] : $paisesPermitidos;

        $cityIds = City::WhereCountriesActive($paisesFiltro)->pluck('id');

        $GruposTrabajoAll = GruposTrabajo::whereIn('city_id', $cityIds)->where('estatus', 1)->get();

        return compact('GruposTrabajoAll');
    }

    /**
     * Autocomplete de usuarios responsables (quien tenía el grupo de trabajo
     * activo cuando se registró lo que se está reportando). Acotado a los
     * `grupos` indicados si se mandan; si no, a todos los grupos del alcance
     * del usuario actual.
     *
     * @return array<string, mixed>
     */
    public function responsables(Request $request): array
    {
        $term = trim((string) $request->query('term', ''));

        if ($term === '') {
            return ['ResponsablesAll' => []];
        }

        $grupos = array_values(array_filter(explode(',', (string) $request->query('grupos', ''))));

        $grupoIds = $grupos !== []
            ? $grupos
            : GruposTrabajo::whereIn(
                'city_id',
                City::WhereCountriesActive($this->paisesPermitidos())->pluck('id'),
            )->pluck('id');

        $userIds = GruposTrabajoUser::whereIn('idgrupo_trabajo', $grupoIds)->pluck('iduser')->unique();

        $ResponsablesAll = User::whereIn('id', $userIds)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return compact('ResponsablesAll');
    }

    /**
     * Catálogo de estados de préstamo (para el filtro de estado).
     *
     * @return array<string, mixed>
     */
    public function estadosPrestamo(): array
    {
        $EstadosAll = PrestamosEstatu::where('estatus', 1)->get(['id', 'description', 'type_tag']);

        return compact('EstadosAll');
    }

    /**
     * Catálogo de estados de informe de pago (Pendiente/Aprobado/Rechazado/
     * En revisión/Remitido), para el filtro de estado.
     *
     * @return array<string, mixed>
     */
    public function estadosInformePago(): array
    {
        $EstadosAll = PaymentReportsMovementsEstatu::where('estatus', 1)->get(['id', 'description']);

        return compact('EstadosAll');
    }
}

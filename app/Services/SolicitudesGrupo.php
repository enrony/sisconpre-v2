<?php

namespace App\Services;

use App\Models\GruposTrabajo;
use App\Models\GruposTrabajoUser;
use App\Models\GrupoTrabajoSolicitud;
use App\Models\User;
use App\Notifications\SolicitudUnionGrupo;
use App\Notifications\SolicitudUnionGrupoResuelta;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Unirse a otro grupo de trabajo con su código: queda una solicitud que
 * aprueba o rechaza el dueño del grupo (`grupos_trabajos.grupos_trabajos_user_id`),
 * o un super-usuario si el grupo no tiene dueño. Cada paso avisa por correo.
 *
 * Las reglas de negocio violadas se informan con `DomainException`.
 */
class SolicitudesGrupo
{
    public function solicitar(User $user, string $codigo): GrupoTrabajoSolicitud
    {
        $grupo = GruposTrabajo::where('code', Str::upper(trim($codigo)))->where('estatus', 1)->first();

        if (! $grupo) {
            throw new DomainException('No encontramos un grupo activo con ese código. Verifíquelo con quien se lo compartió.');
        }

        if (GruposTrabajoUser::where('iduser', $user->id)->where('idgrupo_trabajo', $grupo->id)->where('estatus', 1)->exists()) {
            throw new DomainException("Ya forma parte del grupo {$grupo->nombre}.");
        }

        if (GrupoTrabajoSolicitud::where('user_id', $user->id)->where('idgrupo_trabajo', $grupo->id)->where('estatus', GrupoTrabajoSolicitud::PENDIENTE)->exists()) {
            throw new DomainException("Ya tiene una solicitud pendiente para el grupo {$grupo->nombre}.");
        }

        return $this->crear($user, $grupo, GrupoTrabajoSolicitud::ORIGEN_CODIGO);
    }

    /**
     * Usuario nuevo que se registró con el código del grupo: entra recién
     * cuando lo aprueba el dueño (el código ya lo validó el registro).
     */
    public function alRegistrarse(User $user, GruposTrabajo $grupo): GrupoTrabajoSolicitud
    {
        return $this->crear($user, $grupo, GrupoTrabajoSolicitud::ORIGEN_REGISTRO);
    }

    private function crear(User $user, GruposTrabajo $grupo, string $origen): GrupoTrabajoSolicitud
    {
        $solicitud = GrupoTrabajoSolicitud::create([
            'idgrupo_trabajo' => $grupo->id,
            'user_id' => $user->id,
            'estatus' => GrupoTrabajoSolicitud::PENDIENTE,
            'origen' => $origen,
        ]);

        $this->notificar($this->aprobadores($grupo), new SolicitudUnionGrupo($solicitud));

        return $solicitud;
    }

    /**
     * Solicitudes del propio usuario que siguen esperando respuesta.
     *
     * @return Collection<int, GrupoTrabajoSolicitud>
     */
    public function enviadas(User $user): Collection
    {
        return GrupoTrabajoSolicitud::query()
            ->with('grupo:id,nombre')
            ->where('user_id', $user->id)
            ->where('estatus', GrupoTrabajoSolicitud::PENDIENTE)
            ->oldest()
            ->get();
    }

    /**
     * Quién decide: el dueño del grupo; si no tiene, los super-usuarios.
     *
     * @return Collection<int, User>
     */
    public function aprobadores(GruposTrabajo $grupo): Collection
    {
        $dueno = $grupo->grupos_trabajos_user_id
            ? GruposTrabajoUser::find($grupo->grupos_trabajos_user_id)?->user
            : null;

        // whereHas y no User::role(): si el rol no existe (instalación nueva) no debe trabar el registro.
        return $dueno
            ? new Collection([$dueno])
            : User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->get();
    }

    public function puedeDecidir(User $user, GrupoTrabajoSolicitud $solicitud): bool
    {
        if (GrupoActivo::esSuperUsuario($user)) {
            return true;
        }

        $dueno = $solicitud->grupo?->grupos_trabajos_user_id;

        return $dueno !== null && GruposTrabajoUser::whereKey($dueno)->where('iduser', $user->id)->exists();
    }

    /**
     * Solicitudes pendientes que este usuario puede decidir.
     *
     * @return Collection<int, GrupoTrabajoSolicitud>
     */
    public function porDecidir(User $user): Collection
    {
        return GrupoTrabajoSolicitud::query()
            ->with(['grupo:id,nombre', 'solicitante:id,name,email'])
            ->where('estatus', GrupoTrabajoSolicitud::PENDIENTE)
            ->when(! GrupoActivo::esSuperUsuario($user), fn ($q) => $q->whereHas(
                'grupo',
                fn ($g) => $g->whereIn('grupos_trabajos_user_id', GruposTrabajoUser::where('iduser', $user->id)->pluck('id')),
            ))
            ->oldest()
            ->get();
    }

    public function aprobar(GrupoTrabajoSolicitud $solicitud, User $quien): void
    {
        $this->exigirPendiente($solicitud);

        DB::transaction(function () use ($solicitud, $quien): void {
            $tieneActivo = GruposTrabajoUser::where('iduser', $solicitud->user_id)->where('estatus', 1)->where('current_grupo', 1)->exists();

            // Si ya fue miembro (membresía inactiva), se reactiva en vez de duplicarla.
            GruposTrabajoUser::updateOrCreate(
                ['iduser' => $solicitud->user_id, 'idgrupo_trabajo' => $solicitud->idgrupo_trabajo],
                ['estatus' => 1, 'current_grupo' => $tieneActivo ? 0 : 1],
            );

            $this->cerrar($solicitud, GrupoTrabajoSolicitud::APROBADA, $quien);
        });

        $this->notificar($this->solicitante($solicitud), new SolicitudUnionGrupoResuelta($solicitud));
    }

    public function rechazar(GrupoTrabajoSolicitud $solicitud, User $quien): void
    {
        $this->exigirPendiente($solicitud);
        $this->cerrar($solicitud, GrupoTrabajoSolicitud::RECHAZADA, $quien);
        $this->notificar($this->solicitante($solicitud), new SolicitudUnionGrupoResuelta($solicitud));
    }

    private function exigirPendiente(GrupoTrabajoSolicitud $solicitud): void
    {
        if ($solicitud->estatus !== GrupoTrabajoSolicitud::PENDIENTE) {
            throw new DomainException('Esta solicitud ya fue resuelta.');
        }
    }

    private function cerrar(GrupoTrabajoSolicitud $solicitud, string $estatus, User $quien): void
    {
        $solicitud->update(['estatus' => $estatus, 'decidido_por' => $quien->id, 'decidido_at' => now()]);
    }

    /** @return Collection<int, User> */
    private function solicitante(GrupoTrabajoSolicitud $solicitud): Collection
    {
        return new Collection(array_filter([$solicitud->solicitante]));
    }

    /**
     * El aviso no puede trabar la solicitud: si el correo falla, queda en el log.
     *
     * @param  Collection<int, User>  $usuarios
     */
    private function notificar(Collection $usuarios, Notification $notificacion): void
    {
        foreach ($usuarios as $usuario) {
            rescue(fn () => $usuario->notify($notificacion->locale('es')), report: true);
        }
    }
}

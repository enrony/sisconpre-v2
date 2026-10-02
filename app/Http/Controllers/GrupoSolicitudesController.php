<?php

namespace App\Http\Controllers;

use App\Models\GrupoTrabajoSolicitud;
use App\Services\SolicitudesGrupo;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Unirse a otro grupo de trabajo con su código (solicitud que aprueba el dueño del grupo). */
class GrupoSolicitudesController extends Controller
{
    public function __construct(private SolicitudesGrupo $solicitudes) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate(['codigo' => ['required', 'string', 'max:20']]);

        try {
            $solicitud = $this->solicitudes->solicitar($request->user(), $request->string('codigo')->toString());
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Solicitud enviada al grupo {$solicitud->grupo?->nombre}. Le avisaremos por correo cuando la resuelvan.",
        ]);
    }

    public function aprobar(Request $request, GrupoTrabajoSolicitud $solicitud): JsonResponse
    {
        return $this->decidir($request, $solicitud, true);
    }

    public function rechazar(Request $request, GrupoTrabajoSolicitud $solicitud): JsonResponse
    {
        return $this->decidir($request, $solicitud, false);
    }

    private function decidir(Request $request, GrupoTrabajoSolicitud $solicitud, bool $aprobar): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $this->solicitudes->puedeDecidir($user, $solicitud), 403, 'Solo el dueño del grupo puede resolver esta solicitud.');

        try {
            $aprobar ? $this->solicitudes->aprobar($solicitud, $user) : $this->solicitudes->rechazar($solicitud, $user);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $aprobar ? 'Solicitud aprobada' : 'Solicitud rechazada',
        ]);
    }
}

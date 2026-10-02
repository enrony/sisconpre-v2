<?php

namespace App\Http\Controllers;

use App\Services\GrupoActivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Selector de grupo de trabajo del encabezado. */
class GrupoActivoController extends Controller
{
    /** `gtu_id` = membresía a activar; `null` = "Todos los grupos" (solo super-usuario). */
    public function cambiar(Request $request): JsonResponse
    {
        $request->validate(['gtu_id' => ['present', 'nullable', 'integer']]);

        $gtuId = $request->filled('gtu_id') ? $request->integer('gtu_id') : null;
        $user = $request->user();

        if (! $user || ! GrupoActivo::cambiar($user, $gtuId)) {
            return response()->json(['success' => false, 'message' => 'Ese grupo de trabajo no está disponible para su usuario.'], 422);
        }

        return response()->json(['success' => true]);
    }
}

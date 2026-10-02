/** Membresía del usuario en un grupo de trabajo (prop compartida `gruposTrabajo`). */
export interface GrupoOpcion {
    gtu_id: number;
    grupo_id: number;
    nombre: string;
    ciudad: string | null;
    pais: string | null;
    /** ISO 3166-1 alfa-2 (CO, VE, AR…), para la bandera. */
    pais_codigo: string | null;
    activo: boolean;
}

export interface GruposTrabajoShared {
    opciones: GrupoOpcion[];
    /** Super-usuario mirando "Todos los grupos". */
    todos: boolean;
    puedeVerTodos: boolean;
    /** Nombres de los grupos a los que pidió unirse y todavía no le respondieron. */
    solicitudesEnviadas: string[];
}

/** Solicitud para unirse a un grupo del que el usuario es dueño (Dashboard). */
export interface SolicitudPorDecidir {
    id: number;
    grupo: string | null;
    nombre: string | null;
    email: string | null;
    fecha: string | null;
    /** Usuario nuevo que se registró con el código del grupo. */
    registro: boolean;
}

/** Solicitud del propio usuario que espera la aprobación del dueño del grupo. */
export interface MiSolicitud {
    id: number;
    grupo: string | null;
    fecha: string | null;
}

interface Importe {
    cantidad: number;
    monto: number;
}

/** Resumen de un grupo para el personal (cartera del grupo). */
export interface DatosGrupoPersonal {
    cartera_activa: Importe;
    cobrar_hoy: Importe;
    vencido: Importe;
    pagos_por_revisar: number;
    prestamos_por_aprobar: number;
}

/** Resumen de un grupo para el cliente (solo lo suyo). */
export interface DatosGrupoCliente {
    prestamos_activos: number;
    saldo_pendiente: number;
    proxima_cuota: { fecha: string; monto: number } | null;
    cuotas_vencidas: number;
    pagos_en_revision: number;
}

/** Tarjeta "Mis grupos" del Dashboard. */
export type TarjetaGrupo = GrupoOpcion &
    (
        | { tipo: 'personal'; datos: DatosGrupoPersonal }
        | { tipo: 'cliente'; datos: DatosGrupoCliente }
    );

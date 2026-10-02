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
}

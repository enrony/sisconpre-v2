export interface MaestroField {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'switch' | 'select';
    options?: { label: string; value: string | number }[];
    span?: number; // columnas del grid (de 2), por defecto 2
}

export interface MaestroColumn {
    prop: string;
    label: string;
    width?: number;
    align?: 'left' | 'center' | 'right';
    /** para columnas booleanas: muestra Sí/No */
    boolean?: boolean;
}

export interface MaestroConfig {
    /** endpoint y prefijo de permiso, p. ej. 'payment_forms'. */
    resource: string;
    title: string;
    singular: string;
    /** prop de Inertia con el paginador (por defecto 'lista'). */
    pageProp?: string;
    columns: MaestroColumn[];
    fields: MaestroField[];
}

export type MaestroRow = Record<string, unknown> & {
    id: number;
    por_defecto?: boolean;
};

export interface MaestroField {
    key: string;
    label: string;
    type?: 'text' | 'number' | 'switch' | 'select' | 'date';
    /** opciones estáticas */
    options?: { label: string; value: string | number }[];
    /** opciones dinámicas: clave dentro de la respuesta de `tablesUrl` */
    optionsKey?: string;
    optionLabel?: string; // campo para el label (por defecto 'description')
    optionValue?: string; // campo para el value (por defecto 'id')
    span?: number; // columnas del grid (de 2), por defecto 2
}

export interface MaestroColumn {
    prop: string;
    label: string;
    width?: number;
    align?: 'left' | 'center' | 'right';
    /** para columnas booleanas: muestra Sí/No */
    boolean?: boolean;
    /** resuelve el valor (un id) contra `tables[lookupKey]` mostrando su label */
    lookupKey?: string;
    lookupLabel?: string;
}

export interface MaestroConfig {
    /** endpoint y prefijo de permiso, p. ej. 'payment_forms'. */
    resource: string;
    title: string;
    singular: string;
    /** prop de Inertia con el paginador (por defecto 'lista'). */
    pageProp?: string;
    /** prop de la fila a mostrar en el diálogo de confirmación de borrado (por defecto 'description'). */
    labelProp?: string;
    /** endpoint de datos auxiliares para selects/lookups (p. ej. '/banks/tables'). */
    tablesUrl?: string;
    columns: MaestroColumn[];
    fields: MaestroField[];
}

export type MaestroRow = Record<string, unknown> & {
    id: number;
    por_defecto?: boolean;
};

export type MaestroTables = Record<
    string,
    Array<Record<string, unknown> & { id?: number | string }>
>;

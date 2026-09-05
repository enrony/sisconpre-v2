import { defineStore } from 'pinia';
import http from '@/lib/http';

/** Fila de la lista de préstamos (respuesta de POST /prestamos/records). */
export interface PrestamoRow {
    id: number;
    created: string;
    date_first_pay: string;
    date_last_pay: string;
    cliente: { nombre?: string; apellido?: string; documento?: string } | null;
    monto_prestamo: string | number;
    tasa: string | number;
    utilidad: string | number;
    total: string | number;
    p_estatus?: { id: number; type_tag?: { type?: string } };
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface EstadoPrestamo {
    id: number;
    description: string;
}

interface ClienteOption {
    id: number;
    nombre: string;
    apellido: string | null;
}

type DateRange = [string, string] | null;

interface Filtro {
    clientes: number[];
    estado: number[];
    fecha_registro: DateRange;
    inicio: DateRange;
    fin: DateRange;
    sortColumn: string;
    sortOrder: 'asc' | 'desc';
}

const emptyFiltro = (): Filtro => ({
    clientes: [],
    estado: [],
    fecha_registro: null,
    inicio: null,
    fin: null,
    sortColumn: 'id',
    sortOrder: 'desc',
});

export const usePrestamosStore = defineStore('prestamos', {
    state: () => ({
        lista: null as Paginated<PrestamoRow> | null,
        estados: [] as EstadoPrestamo[],
        clientesLista: [] as ClienteOption[],
        loading: false,
        loadingClientes: false,
        filtro: emptyFiltro(),
    }),

    actions: {
        queryString(page = 1): string {
            const p = new URLSearchParams();
            p.set('page', String(page));

            const ranges: Array<[keyof Filtro, string]> = [
                ['fecha_registro', 'fecha_registro'],
                ['inicio', 'inicio'],
                ['fin', 'fin'],
            ];
            for (const [key, prefix] of ranges) {
                const range = this.filtro[key] as DateRange;
                if (Array.isArray(range) && range.length === 2) {
                    p.set(`${prefix}1`, range[0]);
                    p.set(`${prefix}2`, range[1]);
                }
            }

            if (this.filtro.clientes.length) {
                p.set('clientes', this.filtro.clientes.join(','));
            }
            if (this.filtro.estado.length) {
                p.set('estados', this.filtro.estado.join(','));
            }
            p.set('sortColumn', this.filtro.sortColumn);
            p.set('sortOrder', this.filtro.sortOrder);

            return p.toString();
        },

        async fetchList(page = 1): Promise<void> {
            this.loading = true;
            try {
                const { data } = await http.post(
                    `/prestamos/records?${this.queryString(page)}`,
                );
                this.lista = data.lista;
            } finally {
                this.loading = false;
            }
        },

        async fetchEstados(): Promise<void> {
            const { data } = await http.get('/prestamos/recordsEstados');
            this.estados = data.estados ?? [];
        },

        async searchClientes(query: string): Promise<void> {
            if (!query) {
                return;
            }
            this.loadingClientes = true;
            try {
                const { data } = await http.get(
                    '/clientes/lista-clientes-json-basic',
                    { params: { cliente: query } },
                );
                this.clientesLista = data.clien ?? [];
            } finally {
                this.loadingClientes = false;
            }
        },

        setSort(
            column: string,
            order: 'ascending' | 'descending' | null,
        ): void {
            this.filtro.sortColumn = column || 'id';
            this.filtro.sortOrder = order === 'ascending' ? 'asc' : 'desc';
            void this.fetchList();
        },

        resetFiltro(): void {
            this.filtro = emptyFiltro();
        },
    },
});

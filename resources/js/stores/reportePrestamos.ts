import { defineStore } from 'pinia';
import http from '@/lib/http';
import type { Paginated } from '@/stores/prestamos';

export interface ReportePrestamoRow {
    id: number;
    cliente: { nombre?: string; apellido?: string; documento?: string } | null;
    created: string;
    date_first_pay: string;
    date_last_pay: string;
    monto_prestamo: string | number;
    tasa: string | number;
    utilidad: string | number;
    total: string | number;
    estado: string | null;
    pais: string | null;
    ciudad: string | null;
    grupo: string | null;
}

interface ClienteOption {
    id: number;
    nombre: string;
    apellido: string | null;
    documento?: string;
}

interface LookupOption {
    id: number | string;
    nombre?: string;
    Name?: string;
}

type DateRange = [string, string] | null;

interface Filtro {
    clientes: number[];
    fecha_registro: DateRange;
    pais: string | null;
    ciudad: number | null;
    grupo: number | null;
}

const emptyFiltro = (): Filtro => ({
    clientes: [],
    fecha_registro: null,
    pais: null,
    ciudad: null,
    grupo: null,
});

export const useReportePrestamosStore = defineStore('reportePrestamos', {
    state: () => ({
        lista: null as Paginated<ReportePrestamoRow> | null,
        loading: false,
        filtro: emptyFiltro(),

        clientesLista: [] as ClienteOption[],
        loadingClientes: false,

        countryAll: [] as LookupOption[],
        citiesAll: [] as LookupOption[],
        gruposTrabajoAll: [] as LookupOption[],
    }),

    actions: {
        queryString(page = 1): string {
            const p = new URLSearchParams();
            p.set('page', String(page));

            const range = this.filtro.fecha_registro;
            if (Array.isArray(range) && range.length === 2) {
                p.set('fecha_registro1', range[0]);
                p.set('fecha_registro2', range[1]);
            }
            if (this.filtro.clientes.length) {
                p.set('clientes', this.filtro.clientes.join(','));
            }
            if (this.filtro.pais) {
                p.set('pais', this.filtro.pais);
            }
            if (this.filtro.ciudad) {
                p.set('ciudad', String(this.filtro.ciudad));
            }
            if (this.filtro.grupo) {
                p.set('grupo', String(this.filtro.grupo));
            }

            return p.toString();
        },

        async fetchList(page = 1): Promise<void> {
            this.loading = true;
            try {
                const { data } = await http.get(
                    `/reporte_prestamos/records?${this.queryString(page)}`,
                );
                this.lista = data.lista;
            } finally {
                this.loading = false;
            }
        },

        async fetchTables(): Promise<void> {
            const { data } = await http.get('/reporte_prestamos/tables');
            this.countryAll = data.CountryAll ?? [];
            this.citiesAll = data.CitiesAll ?? [];
            this.gruposTrabajoAll = data.GruposTrabajoAll ?? [];
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

        resetFiltro(): void {
            this.filtro = emptyFiltro();
        },
    },
});

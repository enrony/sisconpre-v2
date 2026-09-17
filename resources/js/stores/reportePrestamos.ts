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
    responsable: string | null;
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

interface EstadoOption {
    id: number;
    description: string;
}

interface ResponsableOption {
    id: number;
    name: string;
    email: string;
}

export interface EstadoBreakdown {
    estatus: number;
    label: string;
    cantidad: number;
    tipo: 'info' | 'success' | 'warning' | 'danger';
}

export interface ReportePrestamosResumen {
    totales: {
        cantidad: number;
        monto_prestado: number;
        utilidad: number;
        monto_perdido: number;
    };
    porEstado: EstadoBreakdown[];
}

type DateRange = [string, string] | null;

interface Filtro {
    clientes: number[];
    fecha_registro: DateRange;
    estados: number[];
    pais: string | null;
    ciudad: number | null;
    grupos: number[];
    responsables: number[];
}

const emptyFiltro = (): Filtro => ({
    clientes: [],
    fecha_registro: null,
    estados: [],
    pais: null,
    ciudad: null,
    grupos: [],
    responsables: [],
});

export const useReportePrestamosStore = defineStore('reportePrestamos', {
    state: () => ({
        lista: null as Paginated<ReportePrestamoRow> | null,
        loading: false,
        filtro: emptyFiltro(),

        resumen: null as ReportePrestamosResumen | null,
        loadingResumen: false,

        clientesLista: [] as ClienteOption[],
        loadingClientes: false,

        estadosAll: [] as EstadoOption[],
        countryAll: [] as LookupOption[],
        citiesAll: [] as LookupOption[],
        loadingCiudades: false,
        gruposTrabajoAll: [] as LookupOption[],
        loadingGrupos: false,
        responsablesLista: [] as ResponsableOption[],
        loadingResponsables: false,
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
            if (this.filtro.estados.length) {
                p.set('estados', this.filtro.estados.join(','));
            }
            if (this.filtro.pais) {
                p.set('pais', this.filtro.pais);
            }
            if (this.filtro.ciudad) {
                p.set('ciudad', String(this.filtro.ciudad));
            }
            if (this.filtro.grupos.length) {
                p.set('grupos', this.filtro.grupos.join(','));
            }
            if (this.filtro.responsables.length) {
                p.set('responsables', this.filtro.responsables.join(','));
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

        async fetchResumen(): Promise<void> {
            this.loadingResumen = true;
            try {
                const { data } = await http.get(
                    `/reporte_prestamos/resumen?${this.queryString()}`,
                );
                this.resumen = data;
            } finally {
                this.loadingResumen = false;
            }
        },

        // --------------------------------------------------------------
        //  Filtros (catálogos + cascada país -> ciudad/grupo -> responsable)
        // --------------------------------------------------------------

        async fetchPaises(): Promise<void> {
            const { data } = await http.get('/reportes/filtros/paises');
            this.countryAll = data.CountryAll ?? [];
        },

        async fetchEstados(): Promise<void> {
            const { data } = await http.get(
                '/reportes/filtros/estados-prestamo',
            );
            this.estadosAll = data.EstadosAll ?? [];
        },

        /** Se llama cuando cambia el país elegido en el filtro. */
        async onPaisChange(): Promise<void> {
            this.filtro.ciudad = null;
            this.filtro.grupos = [];
            await Promise.all([this.fetchCiudades(), this.fetchGrupos()]);
        },

        /** Sin país seleccionado, no se ofrece ninguna ciudad (a propósito). */
        async fetchCiudades(): Promise<void> {
            if (!this.filtro.pais) {
                this.citiesAll = [];

                return;
            }
            this.loadingCiudades = true;
            try {
                const { data } = await http.get('/reportes/filtros/ciudades', {
                    params: { pais: this.filtro.pais },
                });
                this.citiesAll = data.CitiesAll ?? [];
            } finally {
                this.loadingCiudades = false;
            }
        },

        /** Sin país, trae todos los grupos del alcance del usuario. */
        async fetchGrupos(): Promise<void> {
            this.loadingGrupos = true;
            try {
                const { data } = await http.get(
                    '/reportes/filtros/grupos-trabajo',
                    {
                        params: this.filtro.pais
                            ? { pais: this.filtro.pais }
                            : {},
                    },
                );
                this.gruposTrabajoAll = data.GruposTrabajoAll ?? [];
            } finally {
                this.loadingGrupos = false;
            }
        },

        /** Autocomplete de responsables, acotado a los grupos seleccionados. */
        async searchResponsables(term: string): Promise<void> {
            if (!term) {
                return;
            }
            this.loadingResponsables = true;
            try {
                const { data } = await http.get(
                    '/reportes/filtros/responsables',
                    {
                        params: {
                            term,
                            grupos: this.filtro.grupos.join(','),
                        },
                    },
                );
                this.responsablesLista = data.ResponsablesAll ?? [];
            } finally {
                this.loadingResponsables = false;
            }
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
            this.citiesAll = [];
            void this.fetchGrupos();
        },

        // --------------------------------------------------------------
        //  Exportar / Imprimir
        // --------------------------------------------------------------

        urlExportarExcel(): string {
            return `/reporte_prestamos/exportar-excel?${this.queryString()}`;
        },

        urlExportarPdf(inline = false): string {
            const qs = this.queryString();

            return `/reporte_prestamos/exportar-pdf?${qs}${inline ? '&inline=1' : ''}`;
        },
    },
});

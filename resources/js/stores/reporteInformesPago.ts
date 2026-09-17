import { defineStore } from 'pinia';
import http from '@/lib/http';
import type { Paginated } from '@/stores/prestamos';

export interface ReporteInformePagoRow {
    id: number;
    cliente: { nombre?: string; apellido?: string; documento?: string } | null;
    created: string;
    destination: number;
    destination_text: string;
    monto: number;
    estado: string | null;
    metodos: string[];
    bancos: string[];
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
    description?: string;
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

export const DESTINOS = [
    { value: 1, label: 'Pago de cuota(s)' },
    { value: 2, label: 'Saldo a favor' },
];

export interface EstadoBreakdown {
    estatus: number;
    label: string;
    cantidad: number;
    tipo: 'info' | 'success' | 'warning' | 'danger';
}

export interface DestinoBreakdown {
    destination: number;
    label: string;
    cantidad: number;
    monto: number;
}

export interface ReporteInformesPagoResumen {
    totales: {
        cantidad: number;
        monto_total: number;
    };
    porDestino: DestinoBreakdown[];
    porEstado: EstadoBreakdown[];
}

type DateRange = [string, string] | null;

interface Filtro {
    clientes: number[];
    fecha_registro: DateRange;
    estados: number[];
    destinos: number[];
    metodos: number[];
    bancos: number[];
    pais: string | null;
    ciudad: number | null;
    grupos: number[];
    responsables: number[];
}

const emptyFiltro = (): Filtro => ({
    clientes: [],
    fecha_registro: null,
    estados: [],
    destinos: [],
    metodos: [],
    bancos: [],
    pais: null,
    ciudad: null,
    grupos: [],
    responsables: [],
});

export const useReporteInformesPagoStore = defineStore('reporteInformesPago', {
    state: () => ({
        lista: null as Paginated<ReporteInformePagoRow> | null,
        loading: false,
        filtro: emptyFiltro(),

        resumen: null as ReporteInformesPagoResumen | null,
        loadingResumen: false,

        clientesLista: [] as ClienteOption[],
        loadingClientes: false,

        estadosAll: [] as EstadoOption[],
        metodosAll: [] as LookupOption[],
        countryAll: [] as LookupOption[],
        citiesAll: [] as LookupOption[],
        loadingCiudades: false,
        gruposTrabajoAll: [] as LookupOption[],
        loadingGrupos: false,
        bancosAll: [] as LookupOption[],
        loadingBancos: false,
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
            if (this.filtro.destinos.length) {
                p.set('destinos', this.filtro.destinos.join(','));
            }
            if (this.filtro.metodos.length) {
                p.set('metodos', this.filtro.metodos.join(','));
            }
            if (this.filtro.bancos.length) {
                p.set('bancos', this.filtro.bancos.join(','));
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
                    `/reporte_informes_pago/records?${this.queryString(page)}`,
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
                    `/reporte_informes_pago/resumen?${this.queryString()}`,
                );
                this.resumen = data;
            } finally {
                this.loadingResumen = false;
            }
        },

        // --------------------------------------------------------------
        //  Filtros (catálogos + cascada país -> ciudad/grupo/banco -> responsable)
        // --------------------------------------------------------------

        async fetchPaises(): Promise<void> {
            const { data } = await http.get('/reportes/filtros/paises');
            this.countryAll = data.CountryAll ?? [];
        },

        async fetchEstados(): Promise<void> {
            const { data } = await http.get(
                '/reportes/filtros/estados-informe-pago',
            );
            this.estadosAll = data.EstadosAll ?? [];
        },

        async fetchMetodos(): Promise<void> {
            const { data } = await http.get('/payment_methods/tables');
            this.metodosAll = data.PaymentMethod ?? [];
        },

        /** Al cambiar el país elegido: recarga ciudad, grupo y banco. */
        async onPaisChange(): Promise<void> {
            this.filtro.ciudad = null;
            this.filtro.grupos = [];
            await Promise.all([
                this.fetchCiudades(),
                this.fetchGrupos(),
                this.fetchBancos(),
            ]);
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

        /** Sin país, todos los bancos; con país, solo los de ese país. */
        async fetchBancos(): Promise<void> {
            this.loadingBancos = true;
            try {
                const { data } = await http.get(
                    this.filtro.pais
                        ? `/banks/tables/${this.filtro.pais}`
                        : '/banks/tables',
                );
                this.bancosAll = data.Bank ?? [];
            } finally {
                this.loadingBancos = false;
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
            void this.fetchBancos();
        },

        // --------------------------------------------------------------
        //  Exportar / Imprimir
        // --------------------------------------------------------------

        urlExportarExcel(): string {
            return `/reporte_informes_pago/exportar-excel?${this.queryString()}`;
        },

        urlExportarPdf(inline = false): string {
            const qs = this.queryString();

            return `/reporte_informes_pago/exportar-pdf?${qs}${inline ? '&inline=1' : ''}`;
        },
    },
});

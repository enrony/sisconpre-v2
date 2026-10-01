import dayjs from 'dayjs';
import { defineStore } from 'pinia';
import http from '@/lib/http';
import type { Paginated } from '@/stores/prestamos';

export type EstadoCuota = 'vencida' | 'hoy' | 'proxima';

/** Cuota pendiente (respuesta de GET /cuotas/records). */
export interface CuotaPendienteRow {
    id: number;
    fecha: string;
    sigla: string;
    monto: number;
    estado: EstadoCuota;
    dias_vencida: number;
    /** Nº de informe de pago sin resolver sobre esta cuota, si lo hay. */
    informe_en_revision: number | null;
    prestamo_id: number;
    cliente_id: number;
    cliente: string;
    telefono: string | null;
    direccion: string | null;
}

interface Total {
    cantidad: number;
    monto: number;
}

export interface TotalesCuotas {
    vencido: Total;
    hoy: Total;
    proximo: Total;
}

interface Filtro {
    rango: [string, string];
    vencidas: boolean;
    clientes: number[];
}

const hoy = (): string => dayjs().format('YYYY-MM-DD');
const filtroInicial = (): Filtro => ({
    rango: [hoy(), hoy()],
    vencidas: true,
    clientes: [],
});

export const useCuotasStore = defineStore('cuotas', {
    state: () => ({
        lista: null as Paginated<CuotaPendienteRow> | null,
        totales: null as TotalesCuotas | null,
        loading: false,
        filtro: filtroInicial(),
        clientesLista: [] as {
            id: number;
            nombre: string;
            apellido: string | null;
        }[],
    }),

    actions: {
        queryString(page = 1): string {
            const p = new URLSearchParams();
            p.set('page', String(page));
            p.set('desde', this.filtro.rango[0]);
            p.set('hasta', this.filtro.rango[1]);
            p.set('vencidas', this.filtro.vencidas ? '1' : '0');
            if (this.filtro.clientes.length) {
                p.set('clientes', this.filtro.clientes.join(','));
            }

            return p.toString();
        },

        async fetchList(page = 1): Promise<void> {
            this.loading = true;
            try {
                const { data } = await http.get(
                    `/cuotas/records?${this.queryString(page)}`,
                );
                this.lista = data.lista;
                this.totales = data.totales;
            } finally {
                this.loading = false;
            }
        },

        async fetchClientes(): Promise<void> {
            const { data } = await http.get(
                '/clientes/lista-clientes-json-basic',
            );
            this.clientesLista = data.clien ?? [];
        },

        limpiar(): void {
            this.filtro = filtroInicial();
            void this.fetchList(1);
        },
    },
});

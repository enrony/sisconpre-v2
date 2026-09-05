import { defineStore } from 'pinia';
import http from '@/lib/http';
import type { Paginated } from '@/stores/prestamos';

export interface InformePagoRow {
    id: number;
    cliente: { nombre?: string; apellido?: string } | string | null;
    created: string;
    destination: number;
    destination_text: string;
    value_amount: number;
    number_cuotas: number;
    importe: string | number;
    status_description?: {
        id: number;
        desc: string;
        color: string;
        finish_estatus: boolean;
    };
}

export interface EstadoMovimiento {
    id: number;
    description: string;
    action_description: string;
    finish_estatus: boolean | number;
    motivo: boolean | number;
    soporte: boolean | number;
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
    destinoPago: number[];
    fecha_registro: DateRange;
}

const emptyFiltro = (): Filtro => ({
    clientes: [],
    estado: [],
    destinoPago: [],
    fecha_registro: null,
});

export const DESTINOS = [
    { value: 1, label: 'Pago de cuota(s)' },
    { value: 2, label: 'Saldo a favor' },
];

export const usePaymentReportStore = defineStore('paymentReport', {
    state: () => ({
        lista: null as Paginated<InformePagoRow> | null,
        estados: [] as EstadoMovimiento[],
        clientesLista: [] as ClienteOption[],
        loading: false,
        loadingClientes: false,
        filtro: emptyFiltro(),
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
            if (this.filtro.estado.length) {
                p.set('estados', this.filtro.estado.join(','));
            }
            if (this.filtro.destinoPago.length) {
                p.set('destinoPago', this.filtro.destinoPago.join(','));
            }

            return p.toString();
        },

        async fetchList(page = 1): Promise<void> {
            this.loading = true;
            try {
                const { data } = await http.get(
                    `/payment_report/records?${this.queryString(page)}`,
                );
                this.lista = data.lista;
            } finally {
                this.loading = false;
            }
        },

        async fetchEstados(): Promise<void> {
            const { data } = await http.get(
                '/PaymentReportsMovementsEstatu/tables',
            );
            this.estados = data.PaymentReportsMovementsEstatu ?? [];
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

        /**
         * Cambia el estado de un informe de pago (movimiento).
         * El backend espera un campo `data` con el JSON (multipart, por el
         * soporte). Devuelve { success, message }.
         */
        async changeState(payload: {
            id: number;
            estatusActual: number;
            estatusSelected: number;
            motivo: string | null;
        }): Promise<{ success: boolean; message: string }> {
            const form = new FormData();
            form.append(
                'data',
                JSON.stringify({
                    id: payload.id,
                    estatus_actual: payload.estatusActual,
                    estatus_selected: payload.estatusSelected,
                    motivo: payload.motivo,
                    support_image: [],
                }),
            );

            const { data } = await http.post(
                '/payment_report/change_estatus_report',
                form,
            );

            return data;
        },
    },
});

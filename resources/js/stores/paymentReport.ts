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

export interface PaymentMethodOption {
    id: number;
    description: string;
    bank: boolean;
    franchise: boolean;
    reference: boolean;
}

export interface CuotaPendiente {
    id: number;
    date: string;
    cuota: string | number;
    apply: boolean;
    pagado: boolean;
}

export interface PrestamoActivo {
    id: number;
    monto_prestamo: string | number;
    cuota_establecida: string | number;
    prestamos_dias: CuotaPendiente[];
}

interface PagoRow {
    payment_method_id: number | null;
    valor_importe: number;
    bank_id: number | null;
    franquicia_id: number | null;
    referencia: string | null;
}

const nuevoPago = (): PagoRow => ({
    payment_method_id: null,
    valor_importe: 0,
    bank_id: null,
    franquicia_id: null,
    referencia: null,
});

interface ClienteFull {
    id: number;
    documento: string;
    nombre: string;
    apellido?: string | null;
    country_id?: string;
}

export const usePaymentReportStore = defineStore('paymentReport', {
    state: () => ({
        lista: null as Paginated<InformePagoRow> | null,
        estados: [] as EstadoMovimiento[],
        clientesLista: [] as ClienteOption[],
        loading: false,
        loadingClientes: false,
        filtro: emptyFiltro(),

        // --- Informar un pago ---
        informarOpen: false,
        informarSubmitting: false,
        clientesAll: [] as ClienteFull[],
        paymentMethods: [] as PaymentMethodOption[],
        banks: [] as { id: number; description: string }[],
        franquicias: [] as { id: number; description: string }[],
        prestamosActivos: [] as PrestamoActivo[],
        loadingActivos: false,
        informar: {
            clienteId: null as number | null,
            tipoPago: 1,
            cuotas: [] as { id: number; cuota: number }[],
            dataPayments: [nuevoPago()],
        },
    }),

    getters: {
        totalCuotas(state): number {
            return state.informar.cuotas.reduce(
                (a, c) => a + Number(c.cuota),
                0,
            );
        },
        totalPagos(state): number {
            return state.informar.dataPayments.reduce(
                (a, p) => a + Number(p.valor_importe || 0),
                0,
            );
        },
        clienteInformar(state): ClienteFull | undefined {
            return state.clientesAll.find(
                (c) => c.id === state.informar.clienteId,
            );
        },
    },

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

        // --------------------------------------------------------------
        //  Informar un pago
        // --------------------------------------------------------------

        async abrirInformar(): Promise<void> {
            this.informar = {
                clienteId: null,
                tipoPago: 1,
                cuotas: [],
                dataPayments: [nuevoPago()],
            };
            this.prestamosActivos = [];
            this.informarOpen = true;

            const [pm, bk, fr, cl] = await Promise.all([
                http.get('/payment_methods/tables'),
                http.get('/banks/tables'),
                http.get('/franquicias/tables'),
                http.get('/clientes/lista-clientes-json-basic'),
            ]);
            this.paymentMethods = pm.data.PaymentMethod ?? [];
            this.banks = bk.data.Bank ?? [];
            this.franquicias = fr.data.lista ?? [];
            this.clientesAll = cl.data.clien ?? [];
        },

        cerrarInformar(): void {
            this.informarOpen = false;
        },

        async cargarActivos(): Promise<void> {
            this.informar.cuotas = [];
            if (!this.informar.clienteId) {
                this.prestamosActivos = [];

                return;
            }
            this.loadingActivos = true;
            try {
                const { data } = await http.get(
                    `/prestamos/obtenerPrestamosActivos/${this.informar.clienteId}`,
                );
                this.prestamosActivos = Array.isArray(data)
                    ? data
                    : (data.data ?? []);
            } finally {
                this.loadingActivos = false;
            }
        },

        toggleCuota(c: CuotaPendiente): void {
            const i = this.informar.cuotas.findIndex((x) => x.id === c.id);
            if (i >= 0) {
                this.informar.cuotas.splice(i, 1);
            } else {
                this.informar.cuotas.push({ id: c.id, cuota: Number(c.cuota) });
            }
        },

        cuotaSeleccionada(id: number): boolean {
            return this.informar.cuotas.some((c) => c.id === id);
        },

        addPago(): void {
            this.informar.dataPayments.push(nuevoPago());
        },

        removePago(i: number): void {
            this.informar.dataPayments.splice(i, 1);
            if (this.informar.dataPayments.length === 0) {
                this.informar.dataPayments.push(nuevoPago());
            }
        },

        async submitInformar(): Promise<{ success: boolean; message: string }> {
            this.informarSubmitting = true;
            try {
                const cliente = this.clienteInformar;
                const form = new FormData();
                form.append(
                    'data',
                    JSON.stringify({
                        cliente,
                        tipoPago: this.informar.tipoPago,
                        cuotas:
                            this.informar.tipoPago === 1
                                ? this.informar.cuotas
                                : [],
                        dataPayments: this.informar.dataPayments.map((p) => ({
                            ...p,
                            support_image: [],
                        })),
                    }),
                );

                const { data } = await http.post('/payment_report/', form);

                return data;
            } finally {
                this.informarSubmitting = false;
            }
        },
    },
});

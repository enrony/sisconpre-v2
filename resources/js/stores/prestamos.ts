import { router } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { defineStore } from 'pinia';
import http from '@/lib/http';
import {
    applySurcharge,
    calcLastDate,
    type Cuota,
    generateSchedule,
    type Holiday,
    SIGLAS_DIAS,
} from '@/lib/prestamoSchedule';

export interface CuotaDia {
    id: number;
    date: string;
    sigla: string;
    cuota: string | number;
    apply: boolean;
    pagado: boolean;
    demorado: boolean;
    dom: boolean;
    festivo: boolean;
}

/** Fila de la lista de préstamos (respuesta de POST /prestamos/records). */
export interface PrestamoRow {
    id: number;
    cliente_id: number;
    created: string;
    date_first_pay: string;
    date_last_pay: string;
    cliente: { nombre?: string; apellido?: string; documento?: string } | null;
    monto_prestamo: string | number;
    tasa: string | number;
    utilidad: string | number;
    total: string | number;
    p_estatus?: { id: number; type_tag?: { type?: string } };
    prestamos_dias?: CuotaDia[];
    pause_surcharge?: boolean | number;
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

interface TipoPrestamo {
    id: number;
    descripcion: string;
    cantidad: number;
    frecuencias: { descripcion: string };
}

interface CountryOption {
    id: string;
    Name: string;
}

interface PrestamoTables {
    TipoPrestamo: TipoPrestamo[];
    CountryHoliday: Array<Holiday & { country_id: string }>;
    CountryAll: CountryOption[];
}

interface ClienteFull {
    id: number;
    documento: string;
    nombre: string;
    nombre_segundo?: string | null;
    apellido?: string | null;
    apellido_segundo?: string | null;
    telefono?: string | null;
    email?: string | null;
    direccion?: string | null;
    tipo_documento?: { sigla?: string };
}

export interface NuevoCliente {
    idtipo_documento: number | null;
    documento: string;
    nombre: string;
    nombre_segundo: string;
    apellido: string;
    apellido_segundo: string;
    telefono: string;
    email: string;
    direccion: string;
    country_id: string | null;
    department_id: number | null;
    city_id: number | null;
}

export const nuevoClienteVacio = (): NuevoCliente => ({
    idtipo_documento: null,
    documento: '',
    nombre: '',
    nombre_segundo: '',
    apellido: '',
    apellido_segundo: '',
    telefono: '',
    email: '',
    direccion: '',
    country_id: null,
    department_id: null,
    city_id: null,
});

interface ClientesTables {
    CountryAll: { id: string; Name: string }[];
    DepartmentAll: { id: number; nombre: string; country_id: string }[];
    CitiesAll: { id: number; nombre: string; department_id: number }[];
}

interface PrestamoForm {
    cliente_id: number | null;
    clienteSelected: ClienteFull | Record<string, never>;
    country_id: string | null;
    tipo_prestamo_id: number;
    tipo_frecuencia_id: number;
    monto_prestamo: number;
    tasa: number;
    date_first_pay: string;
    date_last_pay: string;
    cuota_sugerida: boolean;
    valor_cuota_sugerida: number;
    incluir_festivos: boolean;
    incluir_domingos: boolean;
    apply_surcharge: boolean;
    surcharge: number;
    days_apply_surcharge: number;
    incluir_festivos_surcharge: boolean;
    incluir_domingos_surcharge: boolean;
    utilidad: number;
    total: number;
    cuota: number;
    cuota_establecida: number;
    pagado: number;
    list_pays: Cuota[];
    stepActive: number;
}

const emptyForm = (): PrestamoForm => ({
    cliente_id: null,
    clienteSelected: {},
    country_id: null,
    tipo_prestamo_id: 1,
    tipo_frecuencia_id: 1,
    monto_prestamo: 250000,
    tasa: 20,
    date_first_pay: dayjs().add(1, 'day').format('YYYY-MM-DD'),
    date_last_pay: '',
    cuota_sugerida: false,
    valor_cuota_sugerida: 0,
    incluir_festivos: false,
    incluir_domingos: false,
    apply_surcharge: true,
    surcharge: 10,
    days_apply_surcharge: 0,
    incluir_festivos_surcharge: false,
    incluir_domingos_surcharge: false,
    utilidad: 0,
    total: 0,
    cuota: 0,
    cuota_establecida: 0,
    pagado: 0,
    list_pays: [],
    stepActive: 0,
});

export const usePrestamosStore = defineStore('prestamos', {
    state: () => ({
        lista: null as Paginated<PrestamoRow> | null,
        estados: [] as EstadoPrestamo[],
        clientesLista: [] as ClienteOption[],
        loading: false,
        loadingClientes: false,
        filtro: emptyFiltro(),

        // --- Asistente de alta ---
        modalOpen: false,
        submitting: false,
        generado: false,
        tables: {
            TipoPrestamo: [],
            CountryHoliday: [],
            CountryAll: [],
        } as PrestamoTables,
        clientesAll: [] as ClienteFull[],
        form: emptyForm(),

        // alta rápida de cliente
        nuevoClienteOpen: false,
        creandoCliente: false,
        clientesTables: {
            CountryAll: [],
            DepartmentAll: [],
            CitiesAll: [],
        } as ClientesTables,
        tiposDocumento: [] as {
            id: number;
            sigla: string;
            nombre: string;
            country_id: string;
        }[],
    }),

    getters: {
        holidaysDelPais(state): Holiday[] {
            if (!state.form.country_id) {
                return [];
            }

            return state.tables.CountryHoliday.filter(
                (h) => h.country_id === state.form.country_id,
            ).map((h) => ({ date: h.date }));
        },
        puedeGenerar(state): boolean {
            return Boolean(
                state.form.date_first_pay &&
                state.form.date_last_pay &&
                state.form.monto_prestamo > 0 &&
                state.form.country_id &&
                !state.generado,
            );
        },
    },

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

        async togglePausaRecargo(
            id: number,
        ): Promise<{ success: boolean; message: string }> {
            const { data } = await http.put(
                `/prestamos/${id}/pausar-recargo`,
                {},
            );
            if (data.success) {
                await this.fetchList(this.lista?.current_page ?? 1);
            }

            return data;
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

        // --------------------------------------------------------------
        //  Asistente de alta de préstamo
        // --------------------------------------------------------------

        async abrirModal(): Promise<void> {
            this.resetForm();
            this.modalOpen = true;
            await Promise.all([this.fetchTables(), this.fetchClientesAll()]);
        },

        cerrarModal(): void {
            this.modalOpen = false;
            this.resetForm();
        },

        resetForm(): void {
            this.form = emptyForm();
            this.generado = false;
        },

        async fetchTables(): Promise<void> {
            const { data } = await http.get('/prestamos/tables');
            this.tables = {
                TipoPrestamo: data.TipoPrestamo ?? [],
                CountryHoliday: data.CountryHoliday ?? [],
                CountryAll: data.CountryAll ?? [],
            };
            if (this.tables.CountryAll.length === 1) {
                this.form.country_id = this.tables.CountryAll[0].id;
            }
        },

        async fetchClientesAll(): Promise<void> {
            const { data } = await http.get(
                '/clientes/lista-clientes-json-basic',
            );
            this.clientesAll = data.clien ?? [];
        },

        async abrirNuevoCliente(): Promise<void> {
            this.nuevoClienteOpen = true;
            if (this.clientesTables.CountryAll.length) {
                return;
            }
            const [t, td] = await Promise.all([
                http.get('/clientes/tables'),
                http.get('/api/listaTipoDocu'),
            ]);
            this.clientesTables = {
                CountryAll: t.data.CountryAll ?? [],
                DepartmentAll: t.data.DepartmentAll ?? [],
                CitiesAll: t.data.CitiesAll ?? [],
            };
            this.tiposDocumento = Array.isArray(td.data)
                ? td.data
                : (td.data.data ?? []);
        },

        async crearClienteRapido(
            payload: Record<string, unknown>,
        ): Promise<{ success: boolean; id?: number }> {
            this.creandoCliente = true;
            try {
                const { data } = await http.put('/user/actualizaCliente', {
                    ...payload,
                    id: 0,
                    otherForm: true,
                    paginaActual: 1,
                });

                if (data.success && data.id) {
                    await this.fetchClientesAll();
                    this.form.cliente_id = data.id;
                    this.seleccionarCliente();
                    this.nuevoClienteOpen = false;
                }

                return data;
            } finally {
                this.creandoCliente = false;
            }
        },

        seleccionarCliente(): void {
            const encontrado = this.clientesAll.find(
                (c) => c.id === this.form.cliente_id,
            );
            this.form.clienteSelected = encontrado ?? {};
            if (this.form.date_first_pay) {
                this.calcularUltimaFecha();
            }
        },

        calcularUltimaFecha(): void {
            const tp = this.tables.TipoPrestamo.find(
                (t) => t.id === this.form.tipo_prestamo_id,
            );
            this.form.date_last_pay =
                tp && this.form.date_first_pay
                    ? calcLastDate(
                          this.form.date_first_pay,
                          tp.cantidad,
                          tp.frecuencias.descripcion as never,
                      )
                    : '';
        },

        generar(): void {
            const freq = this.tables.TipoPrestamo.find(
                (t) => t.id === this.form.tipo_frecuencia_id,
            );
            if (!freq) {
                return;
            }

            const res = generateSchedule({
                dateFirstPay: this.form.date_first_pay,
                dateLastPay: this.form.date_last_pay,
                montoPrestamo: Number(this.form.monto_prestamo),
                tasa: Number(this.form.tasa),
                frecuenciaCantidad: freq.cantidad,
                frecuenciaUnidad: freq.frecuencias.descripcion as never,
                incluirFestivos: this.form.incluir_festivos,
                incluirDomingos: this.form.incluir_domingos,
                cuotaSugerida: this.form.cuota_sugerida,
                valorCuotaSugerida: Number(this.form.valor_cuota_sugerida),
                holidays: this.holidaysDelPais,
            });

            this.form.list_pays = res.listPays;
            this.form.utilidad = res.utilidad;
            this.form.total = res.total;
            this.form.cuota = res.cuota;
            this.form.cuota_establecida = res.cuotaEstablecida;
            this.form.pagado = 0;
            this.generado = true;
        },

        /**
         * Mueve la fecha de una cuota del calendario generado (asistente de alta).
         * Port de `changeDate.vue` del legado: valida festivo/domingo según la
         * configuración del préstamo y que la fecha quede dentro del rango.
         * NO redistribuye importes. Devuelve un mensaje de error o `null`.
         */
        cambiarFechaCuota(index: number, nuevaFecha: string): string | null {
            const cuota = this.form.list_pays[index];
            if (!cuota || !nuevaFecha) {
                return 'Cuota no válida.';
            }

            const nueva = dayjs(nuevaFecha);
            const festivos = new Set(this.holidaysDelPais.map((h) => h.date));
            const esFestivo = festivos.has(nueva.format('YYYY-MM-DD'));
            const esDomingo = nueva.day() === 0;

            if (esFestivo && !this.form.incluir_festivos) {
                return 'El préstamo no permite seleccionar un día festivo.';
            }
            if (esDomingo && !this.form.incluir_domingos) {
                return 'El préstamo no permite seleccionar un domingo.';
            }
            if (nueva.isBefore(dayjs(this.form.date_first_pay), 'day')) {
                return 'La fecha no puede ser anterior a la primera cuota.';
            }
            if (nueva.isAfter(dayjs(this.form.date_last_pay), 'day')) {
                return 'La fecha no puede ser posterior a la última cuota.';
            }

            const hoy = dayjs().startOf('day');
            const iso = nueva.format('YYYY-MM-DD');

            this.form.list_pays[index] = {
                ...cuota,
                date: iso,
                date_change: true,
                date_before: cuota.date,
                festivo: esFestivo,
                dom: esDomingo,
                sigla: SIGLAS_DIAS[nueva.day()],
                diff: hoy.diff(nueva, 'day'),
            };

            return null;
        },

        aplicarRecargo(): void {
            applySurcharge(this.form.list_pays, {
                applySurcharge: this.form.apply_surcharge,
                surcharge: Number(this.form.surcharge),
                daysApplySurcharge: Number(this.form.days_apply_surcharge),
                incluirFestivosSurcharge: this.form.incluir_festivos_surcharge,
                incluirDomingosSurcharge: this.form.incluir_domingos_surcharge,
                holidays: this.holidaysDelPais,
            });
        },

        submitPrestamo(): void {
            this.aplicarRecargo();
            this.submitting = true;

            router.put(
                '/prestamos',
                {
                    ...this.form,
                    clienteSelected: this.form.clienteSelected,
                    list_pays: this.form.list_pays,
                    paginaActual: 1,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.cerrarModal();
                        void this.fetchList(1);
                    },
                    onFinish: () => {
                        this.submitting = false;
                    },
                },
            );
        },
    },
});

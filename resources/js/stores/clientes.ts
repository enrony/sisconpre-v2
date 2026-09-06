import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import http from '@/lib/http';

/** Fila del listado (respuesta de GET /clientes → prop Inertia `lista`). */
export interface ClienteRow {
    id: number;
    documento: string;
    nombre: string;
    nombre_segundo: string | null;
    apellido: string | null;
    apellido_segundo: string | null;
    telefono: string | null;
    email: string | null;
    direccion: string | null;
    idtipo_documento: number;
    city_id: number | null;
    sigla: string;
    full_name: string;
    full_document: string;
    created: string;
}

export interface ClienteForm {
    id: number;
    idtipo_documento: number | null;
    documento: string;
    nombre: string;
    nombre_segundo: string;
    apellido: string;
    apellido_segundo: string;
    telefono: string;
    email: string;
    direccion: string;
    /** solo para la cascada de la UI; el backend guarda `city_id`. */
    country_id: string | null;
    department_id: number | null;
    city_id: number | null;
}

interface TipoDoc {
    id: number;
    sigla: string;
    nombre: string;
    country_id: string;
}
interface CountryOpt {
    id: string;
    Name: string;
}
interface DeptOpt {
    id: number;
    nombre: string;
    country_id: string;
}
interface CityOpt {
    id: number;
    nombre: string;
    department_id: number;
}

const emptyForm = (): ClienteForm => ({
    id: 0,
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

export const useClientesStore = defineStore('clientes', {
    state: () => ({
        modalOpen: false,
        submitting: false,
        editing: null as ClienteRow | null,
        tablesLoaded: false,
        countries: [] as CountryOpt[],
        departments: [] as DeptOpt[],
        cities: [] as CityOpt[],
        tiposDocumento: [] as TipoDoc[],
        form: emptyForm(),
    }),

    actions: {
        async fetchTables(): Promise<void> {
            if (this.tablesLoaded) {
                return;
            }
            const [t, td] = await Promise.all([
                http.get('/clientes/tables'),
                http.get('/api/listaTipoDocu'),
            ]);
            this.countries = t.data.CountryAll ?? [];
            this.departments = t.data.DepartmentAll ?? [];
            this.cities = t.data.CitiesAll ?? [];
            this.tiposDocumento = Array.isArray(td.data)
                ? td.data
                : (td.data.data ?? []);
            this.tablesLoaded = true;
        },

        async abrirModal(row: ClienteRow | null = null): Promise<void> {
            await this.fetchTables();
            this.editing = row;

            if (row) {
                const city = this.cities.find((c) => c.id === row.city_id);
                const dept = city
                    ? this.departments.find((d) => d.id === city.department_id)
                    : undefined;
                this.form = {
                    id: row.id,
                    idtipo_documento: row.idtipo_documento,
                    documento: row.documento ?? '',
                    nombre: row.nombre ?? '',
                    nombre_segundo: row.nombre_segundo ?? '',
                    apellido: row.apellido ?? '',
                    apellido_segundo: row.apellido_segundo ?? '',
                    telefono: row.telefono ?? '',
                    email: row.email ?? '',
                    direccion: row.direccion ?? '',
                    country_id: dept?.country_id ?? null,
                    department_id: city?.department_id ?? null,
                    city_id: row.city_id,
                };
            } else {
                this.form = emptyForm();
            }
            this.modalOpen = true;
        },

        cerrarModal(): void {
            this.modalOpen = false;
        },

        guardar(paginaActual: number): void {
            this.submitting = true;
            router.put(
                '/clientes',
                { ...this.form, paginaActual },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.modalOpen = false;
                    },
                    onFinish: () => {
                        this.submitting = false;
                    },
                },
            );
        },

        eliminar(row: ClienteRow, paginaActual: number): void {
            router.delete(`/clientes/${row.id}/${paginaActual}`, {
                preserveScroll: true,
            });
        },
    },
});

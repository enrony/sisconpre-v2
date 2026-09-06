import { router } from '@inertiajs/vue3';
import { defineStore } from 'pinia';

export interface RoleRow {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
    protegido: boolean;
}

export interface ModuloCatalogo {
    modulo: string;
    permisos: string[];
}

interface RoleForm {
    id: number;
    name: string;
    permissions: string[];
}

export const useRolesStore = defineStore('roles', {
    state: () => ({
        modalOpen: false,
        submitting: false,
        editing: null as RoleRow | null,
        form: { id: 0, name: '', permissions: [] } as RoleForm,
    }),

    actions: {
        abrir(row: RoleRow | null = null): void {
            this.editing = row;
            this.form = row
                ? {
                      id: row.id,
                      name: row.name,
                      permissions: [...row.permissions],
                  }
                : { id: 0, name: '', permissions: [] };
            this.modalOpen = true;
        },

        cerrar(): void {
            this.modalOpen = false;
        },

        toggleModulo(mod: ModuloCatalogo, activar: boolean): void {
            const set = new Set(this.form.permissions);
            for (const p of mod.permisos) {
                if (activar) {
                    set.add(p);
                } else {
                    set.delete(p);
                }
            }
            this.form.permissions = [...set];
        },

        guardar(): void {
            this.submitting = true;
            router.put(
                '/profile',
                { ...this.form },
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

        eliminar(row: RoleRow): void {
            router.delete(`/profile/roles/${row.id}`, { preserveScroll: true });
        },
    },
});

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import type { Paginated } from '@/stores/prestamos';

interface UsuarioRow {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

const page = usePage();

const usuarios = computed(
    () => (page.props.usuarios ?? null) as Paginated<UsuarioRow> | null,
);
const rolesDisponibles = computed(() => (page.props.roles ?? []) as string[]);

const puedeEditar = can('profile.editar');

const term = ref(new URLSearchParams(window.location.search).get('term') ?? '');

function recargar(p = 1) {
    router.get(
        '/profile/usuarios',
        { page: p, term: term.value || undefined },
        { preserveState: true, preserveScroll: true, only: ['usuarios'] },
    );
}

let debounce: ReturnType<typeof setTimeout> | undefined;
function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => recargar(1), 300);
}
onBeforeUnmount(() => clearTimeout(debounce));

const guardando = ref<number | null>(null);

function guardarRoles(row: UsuarioRow, roles: string[]) {
    guardando.value = row.id;
    router.put(
        `/profile/usuarios/${row.id}`,
        { roles, page: usuarios.value?.current_page ?? 1 },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                guardando.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Usuarios y roles" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                title="Usuarios y roles"
                description="Asigna uno o más roles a cada usuario"
            />
            <Link href="/profile">
                <el-button text>Roles y permisos</el-button>
            </Link>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4 max-w-sm">
                <el-input
                    v-model="term"
                    clearable
                    size="small"
                    placeholder="Buscar por nombre o correo"
                    @input="onSearch"
                    @clear="recargar(1)"
                />
            </div>

            <el-table
                :data="usuarios?.data ?? []"
                stripe
                border
                size="small"
                style="width: 100%"
                max-height="560"
            >
                <el-table-column
                    prop="id"
                    label="#"
                    width="72"
                    align="center"
                />
                <el-table-column prop="name" label="Nombre" min-width="180" />
                <el-table-column prop="email" label="Correo" min-width="200" />
                <el-table-column label="Roles" min-width="280">
                    <template #default="{ row }">
                        <el-select
                            :model-value="row.roles"
                            multiple
                            collapse-tags
                            collapse-tags-tooltip
                            filterable
                            size="small"
                            class="w-full"
                            :disabled="!puedeEditar"
                            :loading="guardando === row.id"
                            placeholder="Sin roles"
                            @change="guardarRoles(row, $event)"
                        >
                            <el-option
                                v-for="r in rolesDisponibles"
                                :key="r"
                                :label="r"
                                :value="r"
                            />
                        </el-select>
                    </template>
                </el-table-column>
            </el-table>

            <div v-if="usuarios" class="mt-4 flex justify-end">
                <el-pagination
                    background
                    layout="total, prev, pager, next"
                    :total="usuarios.total"
                    :page-size="usuarios.per_page"
                    :current-page="usuarios.current_page"
                    @current-change="recargar"
                />
            </div>
        </div>
    </div>
</template>

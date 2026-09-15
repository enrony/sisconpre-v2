<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CircleHelp } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import ResponsiveList from '@/components/data/ResponsiveList.vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import type { Paginated } from '@/stores/prestamos';

interface UsuarioRow {
    id: number;
    name: string;
    email: string;
    roles: string[];
    paises: string[];
}

interface PaisOption {
    id: string;
    Name: string;
}

const page = usePage();

const usuarios = computed(
    () => (page.props.usuarios ?? null) as Paginated<UsuarioRow> | null,
);
const rolesDisponibles = computed(() => (page.props.roles ?? []) as string[]);
const paisesDisponibles = computed(
    () => (page.props.paises ?? []) as PaisOption[],
);

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

function guardar(row: UsuarioRow, campo: 'roles' | 'paises', valor: string[]) {
    guardando.value = row.id;
    router.put(
        `/profile/usuarios/${row.id}`,
        { [campo]: valor, page: usuarios.value?.current_page ?? 1 },
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
                description="Asigna roles, y en qué países puede cada usuario crear un grupo de trabajo nuevo"
            />
            <Link href="/profile">
                <el-button text>Roles y permisos</el-button>
            </Link>
        </div>

        <div
            class="bg-card rounded-xl border p-4 shadow-sm max-md:border-0 max-md:bg-transparent max-md:p-0 max-md:shadow-none"
        >
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

            <ResponsiveList
                :rows="usuarios?.data ?? []"
                :fields="[]"
                :title="(row) => row.name"
                :subtitle="(row) => row.email"
                empty="Sin usuarios."
            >
                <template #card="{ row }">
                    <p class="text-foreground text-sm font-semibold">
                        {{ (row as UsuarioRow).name }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        {{ (row as UsuarioRow).email }}
                    </p>

                    <p class="text-muted-foreground mt-2.5 text-xs">Roles</p>
                    <el-select
                        :model-value="(row as UsuarioRow).roles"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        filterable
                        class="mt-1 w-full"
                        :disabled="!puedeEditar"
                        :loading="guardando === (row as UsuarioRow).id"
                        placeholder="Sin roles"
                        @change="guardar(row as UsuarioRow, 'roles', $event)"
                    >
                        <el-option
                            v-for="r in rolesDisponibles"
                            :key="r"
                            :label="r"
                            :value="r"
                        />
                    </el-select>

                    <p class="text-muted-foreground mt-2.5 text-xs">
                        Países donde puede crear un grupo de trabajo
                    </p>
                    <el-select
                        :model-value="(row as UsuarioRow).paises"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        filterable
                        class="mt-1 w-full"
                        :disabled="!puedeEditar"
                        :loading="guardando === (row as UsuarioRow).id"
                        placeholder="Sin países asignados"
                        @change="guardar(row as UsuarioRow, 'paises', $event)"
                    >
                        <el-option
                            v-for="p in paisesDisponibles"
                            :key="p.id"
                            :label="p.Name"
                            :value="p.id"
                        />
                    </el-select>
                </template>

                <template #table>
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
                        <el-table-column
                            prop="name"
                            label="Nombre"
                            min-width="180"
                        />
                        <el-table-column
                            prop="email"
                            label="Correo"
                            min-width="200"
                        />
                        <el-table-column label="Roles" min-width="240">
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
                                    @change="
                                        guardar(
                                            row as UsuarioRow,
                                            'roles',
                                            $event,
                                        )
                                    "
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
                        <el-table-column min-width="260">
                            <template #header>
                                <span class="inline-flex items-center gap-1">
                                    Países
                                    <el-tooltip
                                        content="En qué países puede este usuario crear un grupo de trabajo nuevo — no es el país en el que opera hoy (eso lo determina su grupo de trabajo actual)."
                                        placement="top"
                                    >
                                        <CircleHelp
                                            class="text-muted-foreground size-3.5"
                                        />
                                    </el-tooltip>
                                </span>
                            </template>
                            <template #default="{ row }">
                                <el-select
                                    :model-value="row.paises"
                                    multiple
                                    collapse-tags
                                    collapse-tags-tooltip
                                    filterable
                                    size="small"
                                    class="w-full"
                                    :disabled="!puedeEditar"
                                    :loading="guardando === row.id"
                                    placeholder="Sin países asignados"
                                    @change="
                                        guardar(
                                            row as UsuarioRow,
                                            'paises',
                                            $event,
                                        )
                                    "
                                >
                                    <el-option
                                        v-for="p in paisesDisponibles"
                                        :key="p.id"
                                        :label="p.Name"
                                        :value="p.id"
                                    />
                                </el-select>
                            </template>
                        </el-table-column>
                    </el-table>
                </template>

                <template v-if="usuarios" #pagination>
                    <div class="mt-4 flex justify-end">
                        <el-pagination
                            background
                            layout="total, prev, pager, next"
                            :total="usuarios.total"
                            :page-size="usuarios.per_page"
                            :current-page="usuarios.current_page"
                            @current-change="recargar"
                        />
                    </div>
                </template>
            </ResponsiveList>
        </div>
    </div>
</template>

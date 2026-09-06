<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import RoleFormModal from '@/components/admin/RoleFormModal.vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import {
    type ModuloCatalogo,
    type RoleRow,
    useRolesStore,
} from '@/stores/roles';

const page = usePage();
const store = useRolesStore();

const roles = computed(() => (page.props.roles ?? []) as RoleRow[]);
const catalogo = computed(
    () => (page.props.catalogo ?? []) as ModuloCatalogo[],
);

const puedeEditar = can('profile.editar');
const puedeEliminar = can('profile.eliminar');

function eliminar(row: RoleRow) {
    void ElMessageBox.confirm(
        `¿Eliminar el rol "${row.name}"? Los usuarios perderán los permisos que solo este rol otorgaba.`,
        'Confirmar',
        { type: 'warning', confirmButtonText: 'Sí', cancelButtonText: 'No' },
    ).then(() => store.eliminar(row as RoleRow));
}
</script>

<template>
    <Head title="Roles" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                title="Roles y permisos"
                description="Cada rol concede un conjunto de permisos por módulo"
            />
            <div class="flex gap-2">
                <Link href="/profile/usuarios">
                    <el-button text>Usuarios y roles</el-button>
                </Link>
                <el-button
                    v-if="puedeEditar"
                    type="primary"
                    @click="store.abrir()"
                >
                    Nuevo rol
                </el-button>
            </div>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <el-table
                :data="roles"
                stripe
                border
                size="small"
                style="width: 100%"
            >
                <el-table-column prop="name" label="Rol" min-width="200">
                    <template #default="{ row }">
                        {{ row.name }}
                        <el-tag
                            v-if="row.protegido"
                            size="small"
                            type="info"
                            class="ml-2"
                            >protegido</el-tag
                        >
                    </template>
                </el-table-column>
                <el-table-column
                    label="Permisos"
                    width="120"
                    align="center"
                    prop="permissions"
                >
                    <template #default="{ row }">
                        {{ row.permissions.length }}
                    </template>
                </el-table-column>
                <el-table-column
                    prop="users_count"
                    label="Usuarios"
                    width="110"
                    align="center"
                />
                <el-table-column
                    v-if="puedeEditar || puedeEliminar"
                    label="Operaciones"
                    width="160"
                    align="center"
                    fixed="right"
                >
                    <template #default="{ row }">
                        <div class="flex justify-center gap-1">
                            <el-button
                                v-if="puedeEditar"
                                size="small"
                                @click="store.abrir(row as RoleRow)"
                            >
                                Editar
                            </el-button>
                            <el-button
                                v-if="puedeEliminar && !row.protegido"
                                size="small"
                                type="danger"
                                plain
                                @click="eliminar(row as RoleRow)"
                            >
                                Eliminar
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <RoleFormModal :catalogo="catalogo" />
    </div>
</template>

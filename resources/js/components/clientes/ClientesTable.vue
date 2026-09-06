<script setup lang="ts">
import { can } from '@/lib/can';
import { type ClienteRow, useClientesStore } from '@/stores/clientes';
import type { Paginated } from '@/stores/prestamos';

const props = defineProps<{ paginator: Paginated<ClienteRow> | null }>();
const emit = defineEmits<{ page: [number] }>();

const store = useClientesStore();

const puedeEditar = can('clientes.editar');
const puedeEliminar = can('clientes.eliminar');

function eliminar(row: ClienteRow) {
    void ElMessageBox.confirm(
        `¿Eliminar al cliente "${row.full_name}"?`,
        'Confirmar',
        { type: 'warning', confirmButtonText: 'Sí', cancelButtonText: 'No' },
    ).then(() =>
        store.eliminar(row as ClienteRow, props.paginator?.current_page ?? 1),
    );
}
</script>

<template>
    <div>
        <el-table
            :data="paginator?.data ?? []"
            stripe
            border
            size="small"
            style="width: 100%"
            max-height="560"
        >
            <el-table-column prop="id" label="#" width="72" align="center" />
            <el-table-column
                prop="full_document"
                label="Documento"
                width="160"
            />
            <el-table-column prop="full_name" label="Nombre" min-width="200" />
            <el-table-column prop="telefono" label="Teléfono" width="130" />
            <el-table-column prop="email" label="Correo" min-width="190" />
            <el-table-column prop="created" label="Registrado" width="150" />
            <el-table-column
                v-if="puedeEditar || puedeEliminar"
                label="Operaciones"
                width="150"
                align="center"
                fixed="right"
            >
                <template #default="{ row }">
                    <div class="flex justify-center gap-1">
                        <el-button
                            v-if="puedeEditar"
                            size="small"
                            @click="store.abrirModal(row as ClienteRow)"
                        >
                            Editar
                        </el-button>
                        <el-button
                            v-if="puedeEliminar"
                            size="small"
                            type="danger"
                            plain
                            @click="eliminar(row as ClienteRow)"
                        >
                            Eliminar
                        </el-button>
                    </div>
                </template>
            </el-table-column>
        </el-table>

        <div v-if="paginator" class="mt-4 flex justify-end">
            <el-pagination
                background
                layout="total, prev, pager, next"
                :total="paginator.total"
                :page-size="paginator.per_page"
                :current-page="paginator.current_page"
                @current-change="emit('page', $event)"
            />
        </div>
    </div>
</template>

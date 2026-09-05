<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { formatNumber } from '@/lib/format';
import {
    type InformePagoRow,
    usePaymentReportStore,
} from '@/stores/paymentReport';

const store = usePaymentReportStore();
const { lista, loading } = storeToRefs(store);

function clienteNombre(row: InformePagoRow): string {
    const c =
        typeof row.cliente === 'string'
            ? (JSON.parse(row.cliente || '{}') as {
                  nombre?: string;
                  apellido?: string;
              })
            : (row.cliente ?? {});
    return `${c.nombre ?? ''} ${c.apellido ?? ''}`.trim() || '—';
}

/** Estado del texto legado (`text-gray-400`…) a tipo de `el-tag`. */
function tagType(row: InformePagoRow): string {
    const color = row.status_description?.color ?? '';
    if (color.includes('green')) return 'success';
    if (color.includes('red')) return 'danger';
    if (color.includes('yellow')) return 'warning';
    return 'info';
}

function onPage(page: number) {
    void store.fetchList(page);
}
</script>

<template>
    <div v-loading="loading">
        <el-table
            :data="lista?.data ?? []"
            stripe
            border
            size="small"
            style="width: 100%"
            max-height="560"
        >
            <el-table-column
                prop="id"
                label="#"
                width="70"
                align="center"
                fixed
            />
            <el-table-column prop="created" label="Registrado" width="160" />
            <el-table-column label="Cliente" min-width="170">
                <template #default="{ row }">{{ clienteNombre(row) }}</template>
            </el-table-column>
            <el-table-column
                prop="destination_text"
                label="Destino"
                width="140"
            />
            <el-table-column label="Monto" width="130" align="right">
                <template #default="{ row }">
                    <span class="font-semibold text-blue-600">
                        {{ formatNumber(row.value_amount) }}
                    </span>
                </template>
            </el-table-column>
            <el-table-column
                prop="number_cuotas"
                label="Cuotas"
                width="90"
                align="center"
            />
            <el-table-column label="Estado" width="150" align="center">
                <template #default="{ row }">
                    <el-tag :type="tagType(row)" effect="dark" size="small">
                        {{ row.status_description?.desc ?? '—' }}
                    </el-tag>
                </template>
            </el-table-column>
            <el-table-column
                label="Acciones"
                width="120"
                align="center"
                fixed="right"
            >
                <template #default="{ row }">
                    <el-dropdown trigger="click" size="small">
                        <el-button size="small" type="primary"
                            >Acciones</el-button
                        >
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item disabled
                                    >Ver detalle (#{{
                                        row.id
                                    }})</el-dropdown-item
                                >
                                <el-dropdown-item disabled
                                    >Cambiar estado</el-dropdown-item
                                >
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </template>
            </el-table-column>
        </el-table>

        <div v-if="lista" class="mt-4 flex justify-end">
            <el-pagination
                background
                layout="total, prev, pager, next"
                :total="lista.total"
                :page-size="lista.per_page"
                :current-page="lista.current_page"
                @current-change="onPage"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import ResponsiveList, {
    type ListField,
} from '@/components/data/ResponsiveList.vue';
import { formatNumber } from '@/lib/format';
import {
    type ReportePrestamoRow,
    useReportePrestamosStore,
} from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();
const { lista, loading } = storeToRefs(store);

function clienteNombre(row: ReportePrestamoRow): string {
    const c = row.cliente ?? {};
    return `${c.nombre ?? ''} ${c.apellido ?? ''}`.trim() || '—';
}

function onPage(page: number) {
    void store.fetchList(page);
}

const fields: ListField<ReportePrestamoRow>[] = [
    { label: 'Registrado', value: (r) => r.created },
    {
        label: 'Monto',
        class: 'text-right font-semibold tabular-nums',
        value: (r) => formatNumber(r.monto_prestamo),
    },
    { label: 'País', value: (r) => r.pais ?? '—' },
    { label: 'Ciudad', value: (r) => r.ciudad ?? '—' },
    { label: 'Grupo', value: (r) => r.grupo ?? '—' },
    { label: 'Estado', value: (r) => r.estado ?? '—' },
];
</script>

<template>
    <ResponsiveList
        :rows="lista?.data ?? []"
        :fields="fields"
        :title="clienteNombre"
        :subtitle="(row) => `#${row.id}`"
        :loading="loading"
        empty="Sin préstamos para los filtros aplicados."
    >
        <template #table>
            <el-table
                :data="lista?.data ?? []"
                stripe
                border
                size="small"
                style="width: 100%"
                max-height="600"
            >
                <el-table-column
                    prop="id"
                    label="#"
                    width="70"
                    align="center"
                    fixed
                />
                <el-table-column
                    prop="created"
                    label="Registrado"
                    width="160"
                />
                <el-table-column label="Cliente" min-width="180">
                    <template #default="{ row }">{{
                        clienteNombre(row as ReportePrestamoRow)
                    }}</template>
                </el-table-column>
                <el-table-column label="Monto" width="130" align="right">
                    <template #default="{ row }">
                        {{ formatNumber(row.monto_prestamo) }}
                    </template>
                </el-table-column>
                <el-table-column label="Utilidad" width="120" align="right">
                    <template #default="{ row }">
                        {{ formatNumber(row.utilidad) }}
                    </template>
                </el-table-column>
                <el-table-column
                    prop="pais"
                    label="País"
                    width="120"
                    align="center"
                />
                <el-table-column
                    prop="ciudad"
                    label="Ciudad"
                    width="150"
                    align="center"
                />
                <el-table-column
                    prop="grupo"
                    label="Grupo"
                    width="130"
                    align="center"
                />
                <el-table-column label="Estado" width="120" align="center">
                    <template #default="{ row }">
                        <el-tag size="small">{{ row.estado ?? '—' }}</el-tag>
                    </template>
                </el-table-column>
            </el-table>
        </template>

        <template v-if="lista" #pagination>
            <div class="mt-4 flex justify-end">
                <el-pagination
                    background
                    layout="total, prev, pager, next"
                    :total="lista.total"
                    :page-size="lista.per_page"
                    :current-page="lista.current_page"
                    @current-change="onPage"
                />
            </div>
        </template>
    </ResponsiveList>
</template>

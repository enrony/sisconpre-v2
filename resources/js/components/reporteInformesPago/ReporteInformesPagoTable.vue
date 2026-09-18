<script setup lang="ts">
import { storeToRefs } from 'pinia';
import ResponsiveList, {
    type ListField,
} from '@/components/data/ResponsiveList.vue';
import { formatNumber } from '@/lib/format';
import {
    type ReporteInformePagoRow,
    useReporteInformesPagoStore,
} from '@/stores/reporteInformesPago';

const store = useReporteInformesPagoStore();
const { lista, loading } = storeToRefs(store);

function clienteNombre(row: ReporteInformePagoRow): string {
    const c = row.cliente ?? {};
    return `${c.nombre ?? ''} ${c.apellido ?? ''}`.trim() || '—';
}

function lista_o_guion(items: string[]): string {
    return items.length ? items.join(', ') : '—';
}

function onPage(page: number) {
    void store.fetchList(page);
}

const fields: ListField<ReporteInformePagoRow>[] = [
    { label: 'Registrado', value: (r) => r.created },
    {
        label: 'Monto',
        class: 'text-right font-semibold tabular-nums',
        value: (r) => formatNumber(r.monto),
    },
    { label: 'Destino', value: (r) => r.destination_text },
    { label: 'Métodos', value: (r) => lista_o_guion(r.metodos) },
    { label: 'Bancos', value: (r) => lista_o_guion(r.bancos) },
    { label: 'País', value: (r) => r.pais ?? '—' },
    { label: 'Grupo', value: (r) => r.grupo ?? '—' },
    { label: 'Responsable', value: (r) => r.responsable ?? '—' },
];
</script>

<template>
    <ResponsiveList
        :rows="lista?.data ?? []"
        :fields="fields"
        :title="clienteNombre"
        :subtitle="(row) => `#${row.id}`"
        :loading="loading"
        empty="Sin informes de pago para los filtros aplicados."
    >
        <template #badge="{ row }">
            <el-tag
                v-if="(row as ReporteInformePagoRow).estado"
                size="small"
                class="shrink-0"
            >
                {{ (row as ReporteInformePagoRow).estado }}
            </el-tag>
        </template>

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
                <el-table-column label="Cliente" min-width="170">
                    <template #default="{ row }">{{
                        clienteNombre(row as ReporteInformePagoRow)
                    }}</template>
                </el-table-column>
                <el-table-column label="Monto" width="120" align="right">
                    <template #default="{ row }">
                        {{ formatNumber(row.monto) }}
                    </template>
                </el-table-column>
                <el-table-column
                    prop="destination_text"
                    label="Destino"
                    width="130"
                />
                <el-table-column label="Métodos" min-width="150">
                    <template #default="{ row }">
                        {{ lista_o_guion(row.metodos) }}
                    </template>
                </el-table-column>
                <el-table-column label="Bancos" min-width="140">
                    <template #default="{ row }">
                        {{ lista_o_guion(row.bancos) }}
                    </template>
                </el-table-column>
                <el-table-column
                    prop="pais"
                    label="País"
                    width="110"
                    align="center"
                />
                <el-table-column
                    prop="grupo"
                    label="Grupo"
                    width="120"
                    align="center"
                />
                <el-table-column
                    prop="responsable"
                    label="Responsable"
                    width="180"
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

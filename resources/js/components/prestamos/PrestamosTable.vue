<script setup lang="ts">
import { storeToRefs } from 'pinia';
import CuotasReadonlyGrid from '@/components/prestamos/CuotasReadonlyGrid.vue';
import PrestamoLegend from '@/components/prestamos/PrestamoLegend.vue';
import { usePrestamosStore } from '@/stores/prestamos';
import { formatNumber } from '@/lib/format';

const store = usePrestamosStore();
const { lista, loading } = storeToRefs(store);

function onSortChange({
    prop,
    order,
}: {
    prop: string;
    order: 'ascending' | 'descending' | null;
}) {
    store.setSort(prop, order);
}

function onPage(page: number) {
    void store.fetchList(page);
}

function tagType(row: {
    p_estatus?: { type_tag?: { type?: string } };
}): string {
    return row.p_estatus?.type_tag?.type ?? 'info';
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
            :default-sort="{ prop: 'id', order: 'descending' }"
            @sort-change="onSortChange"
        >
            <el-table-column type="expand" width="32">
                <template #default="{ row }">
                    <div class="space-y-3 p-3">
                        <PrestamoLegend :dias="row.prestamos_dias ?? []" />
                        <CuotasReadonlyGrid :dias="row.prestamos_dias ?? []" />
                    </div>
                </template>
            </el-table-column>
            <el-table-column
                prop="id"
                label="#"
                width="72"
                align="center"
                fixed
                sortable="custom"
            >
                <template #default="{ row }">
                    <el-tag :type="tagType(row)" effect="dark" size="small">{{
                        row.id
                    }}</el-tag>
                </template>
            </el-table-column>
            <el-table-column
                prop="created"
                label="Registrado"
                width="160"
                sortable="custom"
            />
            <el-table-column
                prop="date_first_pay"
                label="Inicia"
                width="110"
                sortable="custom"
            />
            <el-table-column
                prop="date_last_pay"
                label="Finaliza"
                width="110"
                sortable="custom"
            />
            <el-table-column label="Cliente" min-width="180">
                <template #default="{ row }">
                    {{ row.cliente?.nombre }} {{ row.cliente?.apellido }}
                </template>
            </el-table-column>
            <el-table-column
                prop="monto_prestamo"
                label="Préstamo"
                width="130"
                align="right"
                sortable="custom"
            >
                <template #default="{ row }">
                    <span class="text-blue-600">{{
                        formatNumber(row.monto_prestamo)
                    }}</span>
                </template>
            </el-table-column>
            <el-table-column
                prop="tasa"
                label="% tasa"
                width="90"
                align="center"
            >
                <template #default="{ row }">{{ Number(row.tasa) }}</template>
            </el-table-column>
            <el-table-column
                prop="utilidad"
                label="Utilidad"
                width="130"
                align="right"
                sortable="custom"
            >
                <template #default="{ row }">{{
                    formatNumber(row.utilidad)
                }}</template>
            </el-table-column>
            <el-table-column
                prop="total"
                label="Total a pagar"
                width="140"
                align="right"
                sortable="custom"
            >
                <template #default="{ row }">
                    <span class="font-bold text-green-600">{{
                        formatNumber(row.total)
                    }}</span>
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
                                <el-dropdown-item disabled>
                                    Informar un pago (#{{ row.id }})
                                </el-dropdown-item>
                                <el-dropdown-item disabled
                                    >Novedades</el-dropdown-item
                                >
                                <el-dropdown-item disabled
                                    >Pausar</el-dropdown-item
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

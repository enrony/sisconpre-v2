<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { ref } from 'vue';
import ResponsiveList, {
    type ListField,
} from '@/components/data/ResponsiveList.vue';
import PrestamoDetalleSheet from '@/components/prestamos/PrestamoDetalleSheet.vue';
import { can } from '@/lib/can';
import { formatNumber } from '@/lib/format';
import { usePaymentReportStore } from '@/stores/paymentReport';
import { type PrestamoRow, usePrestamosStore } from '@/stores/prestamos';

const store = usePrestamosStore();
const { lista, loading } = storeToRefs(store);
const paymentReportStore = usePaymentReportStore();

const puedeEditar = can('prestamos.editar');
const puedeInformarPago =
    can('payment_report.registrar') ||
    can('payment_report.gestionar-informe-de-pago');

function informarPago(row: PrestamoRow) {
    void paymentReportStore.abrirInformar(row.cliente_id);
}

const detalleOpen = ref(false);
const detalleRow = ref<PrestamoRow | null>(null);

function abrirDetalle(row: PrestamoRow) {
    detalleRow.value = row;
    detalleOpen.value = true;
}

function onRowClick(row: PrestamoRow, column: { label?: string } | null): void {
    // Los clics en la columna de acciones no abren el detalle.
    if (column?.label === 'Acciones') return;
    abrirDetalle(row);
}

async function togglePausa(row: PrestamoRow) {
    try {
        const res = await store.togglePausaRecargo(row.id);
        ElNotification[res.success ? 'success' : 'error'](res.message);
    } catch {
        ElNotification.error('No se pudo cambiar la pausa del recargo');
    }
}

function onSortChange({
    prop,
    order,
}: {
    prop: string | null;
    order: 'ascending' | 'descending' | null;
}) {
    store.setSort(prop ?? 'id', order);
}

function onPage(page: number) {
    void store.fetchList(page);
}

type TagType = 'primary' | 'success' | 'info' | 'warning' | 'danger';
const TAG_TYPES: TagType[] = [
    'primary',
    'success',
    'info',
    'warning',
    'danger',
];

function tagType(row: PrestamoRow): TagType {
    const t = row.p_estatus?.type_tag?.type ?? '';
    return TAG_TYPES.includes(t as TagType) ? (t as TagType) : 'info';
}

const clienteNombre = (row: PrestamoRow): string =>
    `${row.cliente?.nombre ?? ''} ${row.cliente?.apellido ?? ''}`.trim() || '—';

const fields: ListField<PrestamoRow>[] = [
    { label: 'Registrado', value: (r) => r.created },
    { label: 'Inicia', value: (r) => r.date_first_pay },
    { label: 'Finaliza', value: (r) => r.date_last_pay },
    {
        label: 'Préstamo',
        class: 'text-right tabular-nums',
        value: (r) => formatNumber(r.monto_prestamo),
    },
    {
        label: '% tasa',
        class: 'tabular-nums',
        value: (r) => Number(r.tasa),
    },
    {
        label: 'Total a pagar',
        class: 'text-right font-semibold tabular-nums',
        value: (r) => formatNumber(r.total),
    },
];
</script>

<template>
    <ResponsiveList
        :rows="lista?.data ?? []"
        :fields="fields"
        :title="clienteNombre"
        :loading="loading"
        empty="Sin préstamos."
        clickable
        @row-click="abrirDetalle"
    >
        <template #badge="{ row }">
            <span class="flex shrink-0 items-center gap-1">
                <el-tag
                    :type="tagType(row as PrestamoRow)"
                    effect="dark"
                    size="small"
                >
                    #{{ row.id }}
                </el-tag>
                <el-tooltip
                    v-if="row.pause_surcharge"
                    content="Recargo por mora pausado"
                >
                    <span class="text-yellow-600">⏸</span>
                </el-tooltip>
            </span>
        </template>

        <template #actions="{ row }">
            <el-button size="default" @click="abrirDetalle(row as PrestamoRow)">
                Ver detalle
            </el-button>
            <el-button
                v-if="puedeInformarPago"
                size="default"
                type="success"
                plain
                @click="informarPago(row as PrestamoRow)"
            >
                Informar un pago
            </el-button>
            <el-button
                v-if="puedeEditar"
                size="default"
                plain
                @click="togglePausa(row as PrestamoRow)"
            >
                {{
                    row.pause_surcharge ? 'Reanudar recargo' : 'Pausar recargo'
                }}
            </el-button>
        </template>

        <template #table>
            <el-table
                :data="lista?.data ?? []"
                stripe
                border
                size="small"
                style="width: 100%"
                max-height="560"
                row-class-name="cursor-pointer"
                :default-sort="{ prop: 'id', order: 'descending' }"
                @sort-change="onSortChange"
                @row-click="onRowClick"
            >
                <el-table-column
                    prop="id"
                    label="#"
                    width="72"
                    align="center"
                    fixed
                    sortable="custom"
                >
                    <template #default="{ row }">
                        <el-tag
                            :type="tagType(row as PrestamoRow)"
                            effect="dark"
                            size="small"
                            >{{ row.id }}</el-tag
                        >
                        <el-tooltip
                            v-if="row.pause_surcharge"
                            content="Recargo por mora pausado"
                        >
                            <span class="ml-1 text-yellow-600">⏸</span>
                        </el-tooltip>
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
                    <template #default="{ row }">{{
                        Number(row.tasa)
                    }}</template>
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
                            <el-button size="small" type="primary" @click.stop>
                                Acciones
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item
                                        @click="
                                            abrirDetalle(row as PrestamoRow)
                                        "
                                    >
                                        Ver detalle (#{{ row.id }})
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        :disabled="!puedeInformarPago"
                                        @click="
                                            informarPago(row as PrestamoRow)
                                        "
                                    >
                                        Informar un pago
                                    </el-dropdown-item>
                                    <el-dropdown-item disabled>
                                        Novedades
                                    </el-dropdown-item>
                                    <el-dropdown-item
                                        :disabled="!puedeEditar"
                                        @click="togglePausa(row as PrestamoRow)"
                                    >
                                        {{
                                            row.pause_surcharge
                                                ? 'Reanudar recargo'
                                                : 'Pausar recargo'
                                        }}
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
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

    <PrestamoDetalleSheet v-model:open="detalleOpen" :row="detalleRow" />
</template>

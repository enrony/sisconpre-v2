<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarClock, CalendarDays, Phone, TriangleAlert } from '@lucide/vue';
import dayjs from 'dayjs';
import { storeToRefs } from 'pinia';
import { computed, onMounted, watch } from 'vue';
import ResponsiveList, {
    type ListField,
} from '@/components/data/ResponsiveList.vue';
import Heading from '@/components/Heading.vue';
import InformarPagoModal from '@/components/paymentReport/InformarPagoModal.vue';
import ReporteKpiTile from '@/components/reportes/ReporteKpiTile.vue';
import { can } from '@/lib/can';
import { formatNumber } from '@/lib/format';
import { type CuotaPendienteRow, useCuotasStore } from '@/stores/cuotas';
import { usePaymentReportStore } from '@/stores/paymentReport';

const props = defineProps<{ esCliente: boolean }>();

const store = useCuotasStore();
const { lista, totales, loading, filtro, clientesLista } = storeToRefs(store);
const pagos = usePaymentReportStore();
const { informarOpen } = storeToRefs(pagos);

const puedeInformar =
    can('payment_report.registrar') ||
    can('payment_report.gestionar-informe-de-pago');

const titulo = computed(() =>
    props.esCliente ? 'Mis cuotas por pagar' : 'Cuotas por cobrar',
);
const descripcion = computed(() =>
    props.esCliente
        ? 'Las cuotas de sus préstamos, de la más próxima a la más lejana'
        : 'Lo que hay que cobrar en sus grupos de trabajo',
);

const atajos = [
    { text: 'Hoy', value: () => [new Date(), new Date()] },
    {
        text: 'Próximos 7 días',
        value: () => [new Date(), dayjs().add(7, 'day').toDate()],
    },
    {
        text: 'Este mes',
        value: () => [new Date(), dayjs().endOf('month').toDate()],
    },
];

function aplicar() {
    void store.fetchList(1);
}

function informarPago(row: CuotaPendienteRow) {
    void pagos.abrirInformar(row.cliente_id);
}

// Al cerrar "Informar un pago", la cuota pasa a "en revisión": se recarga.
watch(informarOpen, (abierto, antes) => {
    if (antes && !abierto) {
        void store.fetchList(lista.value?.current_page ?? 1);
    }
});

type TagType = 'danger' | 'warning' | 'info';

function estadoTag(row: CuotaPendienteRow): { tipo: TagType; texto: string } {
    if (row.estado === 'vencida') {
        return {
            tipo: 'danger',
            texto:
                row.dias_vencida === 1
                    ? 'Vencida ayer'
                    : `Vencida hace ${row.dias_vencida} días`,
        };
    }
    if (row.estado === 'hoy') {
        return { tipo: 'warning', texto: 'Vence hoy' };
    }
    return { tipo: 'info', texto: 'Próxima' };
}

const fechaLarga = (iso: string): string => dayjs(iso).format('DD/MM/YYYY');

const fields = computed<ListField<CuotaPendienteRow>[]>(() => [
    // Para el cliente la fecha ya es el título de la tarjeta.
    ...(props.esCliente
        ? []
        : [
              {
                  label: 'Vence',
                  value: (r: CuotaPendienteRow) => fechaLarga(r.fecha),
              },
          ]),
    {
        label: 'Cuota',
        class: 'text-right font-semibold tabular-nums',
        value: (r) => formatNumber(r.monto),
    },
    { label: 'Préstamo', value: (r) => `#${r.prestamo_id}` },
    ...(props.esCliente
        ? []
        : [
              {
                  label: 'Dirección',
                  value: (r: CuotaPendienteRow) => r.direccion ?? '—',
              },
          ]),
]);

onMounted(() => {
    void store.fetchList(1);
    if (!props.esCliente) {
        void store.fetchClientes();
    }
});
</script>

<template>
    <Head :title="titulo" />

    <div class="px-4 py-6">
        <Heading :title="titulo" :description="descripcion" />

        <!-- Filtros -->
        <div
            class="bg-card mb-4 flex flex-wrap items-end gap-3 rounded-xl border p-4 shadow-sm"
        >
            <div class="w-full sm:w-auto">
                <label
                    class="text-muted-foreground mb-1 block text-xs font-semibold"
                    >Vencen entre</label
                >
                <el-date-picker
                    v-model="filtro.rango"
                    type="daterange"
                    size="small"
                    class="!w-full sm:!w-64"
                    value-format="YYYY-MM-DD"
                    format="DD/MM/YYYY"
                    start-placeholder="Desde"
                    end-placeholder="Hasta"
                    :clearable="false"
                    :shortcuts="atajos"
                />
            </div>
            <div v-if="!esCliente" class="w-full sm:w-64">
                <label
                    class="text-muted-foreground mb-1 block text-xs font-semibold"
                    >Clientes</label
                >
                <el-select
                    v-model="filtro.clientes"
                    multiple
                    collapse-tags
                    filterable
                    clearable
                    size="small"
                    class="w-full"
                    placeholder="Todos"
                >
                    <el-option
                        v-for="c in clientesLista"
                        :key="c.id"
                        :label="`${c.nombre} ${c.apellido ?? ''}`"
                        :value="c.id"
                    />
                </el-select>
            </div>
            <el-checkbox v-model="filtro.vencidas" size="small">
                Incluir vencidas anteriores
            </el-checkbox>
            <div class="flex gap-2">
                <el-button type="primary" size="small" @click="aplicar"
                    >Aplicar</el-button
                >
                <el-button size="small" @click="store.limpiar()"
                    >Limpiar</el-button
                >
            </div>
        </div>

        <!-- Totales del filtro: franja compacta en celular, para que la lista quede a la vista -->
        <dl
            v-if="totales"
            class="bg-card mb-3 grid grid-cols-3 divide-x rounded-xl border text-center sm:hidden"
        >
            <div class="px-2 py-2">
                <dt class="text-muted-foreground text-[11px]">Vencido</dt>
                <dd
                    class="text-sm font-semibold tabular-nums"
                    :class="
                        totales.vencido.cantidad > 0
                            ? 'text-destructive'
                            : 'text-foreground'
                    "
                >
                    {{ formatNumber(totales.vencido.monto) }}
                </dd>
                <dd class="text-muted-foreground text-[11px]">
                    {{ totales.vencido.cantidad }} cuota(s)
                </dd>
            </div>
            <div class="px-2 py-2">
                <dt class="text-muted-foreground text-[11px]">Hoy</dt>
                <dd class="text-foreground text-sm font-semibold tabular-nums">
                    {{ formatNumber(totales.hoy.monto) }}
                </dd>
                <dd class="text-muted-foreground text-[11px]">
                    {{ totales.hoy.cantidad }} cuota(s)
                </dd>
            </div>
            <div class="px-2 py-2">
                <dt class="text-muted-foreground text-[11px]">Próximas</dt>
                <dd class="text-foreground text-sm font-semibold tabular-nums">
                    {{ formatNumber(totales.proximo.monto) }}
                </dd>
                <dd class="text-muted-foreground text-[11px]">
                    {{ totales.proximo.cantidad }} cuota(s)
                </dd>
            </div>
        </dl>
        <div
            v-if="totales"
            class="mb-4 hidden gap-3 sm:grid sm:grid-cols-3"
            v-loading="loading"
        >
            <ReporteKpiTile
                label="Vencido"
                :value="formatNumber(totales.vencido.monto)"
                :sublabel="`${totales.vencido.cantidad} cuota(s)`"
                :tone="totales.vencido.cantidad > 0 ? 'destructive' : 'default'"
                :icon="TriangleAlert"
            />
            <ReporteKpiTile
                label="Vence hoy"
                :value="formatNumber(totales.hoy.monto)"
                :sublabel="`${totales.hoy.cantidad} cuota(s)`"
                :icon="CalendarClock"
            />
            <ReporteKpiTile
                label="Próximas en el rango"
                :value="formatNumber(totales.proximo.monto)"
                :sublabel="`${totales.proximo.cantidad} cuota(s)`"
                :icon="CalendarDays"
            />
        </div>

        <div
            class="bg-card rounded-xl border p-4 shadow-sm max-md:border-0 max-md:bg-transparent max-md:p-0 max-md:shadow-none"
        >
            <ResponsiveList
                :rows="lista?.data ?? []"
                :fields="fields"
                :title="
                    (r) =>
                        esCliente ? `Vence ${fechaLarga(r.fecha)}` : r.cliente
                "
                :subtitle="(r) => (esCliente ? '' : (r.telefono ?? ''))"
                :loading="loading"
                empty="No hay cuotas pendientes para este filtro."
            >
                <template #badge="{ row }">
                    <span class="flex shrink-0 flex-col items-end gap-1">
                        <el-tag
                            :type="estadoTag(row as CuotaPendienteRow).tipo"
                            size="small"
                        >
                            {{ estadoTag(row as CuotaPendienteRow).texto }}
                        </el-tag>
                        <el-tag
                            v-if="
                                (row as CuotaPendienteRow).informe_en_revision
                            "
                            type="info"
                            effect="plain"
                            size="small"
                        >
                            Pago en revisión #{{
                                (row as CuotaPendienteRow).informe_en_revision
                            }}
                        </el-tag>
                    </span>
                </template>

                <template v-if="puedeInformar || !esCliente" #actions="{ row }">
                    <el-button
                        v-if="
                            puedeInformar &&
                            !(row as CuotaPendienteRow).informe_en_revision
                        "
                        size="default"
                        type="success"
                        plain
                        @click="informarPago(row as CuotaPendienteRow)"
                    >
                        Informar un pago
                    </el-button>
                    <el-button
                        v-if="!esCliente && (row as CuotaPendienteRow).telefono"
                        tag="a"
                        :href="`tel:${(row as CuotaPendienteRow).telefono}`"
                        size="default"
                        plain
                        :icon="Phone"
                    >
                        Llamar
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
                    >
                        <el-table-column label="Vence" width="110">
                            <template #default="{ row }">{{
                                fechaLarga(row.fecha)
                            }}</template>
                        </el-table-column>
                        <el-table-column label="Estado" width="170">
                            <template #default="{ row }">
                                <el-tag
                                    :type="
                                        estadoTag(row as CuotaPendienteRow).tipo
                                    "
                                    size="small"
                                >
                                    {{
                                        estadoTag(row as CuotaPendienteRow)
                                            .texto
                                    }}
                                </el-tag>
                                <el-tooltip
                                    v-if="row.informe_en_revision"
                                    :content="`Hay un pago informado sin resolver (informe #${row.informe_en_revision})`"
                                >
                                    <el-tag
                                        type="info"
                                        effect="plain"
                                        size="small"
                                        class="ml-1"
                                        >En revisión</el-tag
                                    >
                                </el-tooltip>
                            </template>
                        </el-table-column>
                        <el-table-column
                            v-if="!esCliente"
                            prop="cliente"
                            label="Cliente"
                            min-width="180"
                        />
                        <el-table-column
                            v-if="!esCliente"
                            label="Teléfono"
                            width="130"
                        >
                            <template #default="{ row }">
                                <a
                                    v-if="row.telefono"
                                    :href="`tel:${row.telefono}`"
                                    class="text-foreground underline-offset-2 hover:underline"
                                    >{{ row.telefono }}</a
                                >
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </template>
                        </el-table-column>
                        <el-table-column label="Préstamo" width="100">
                            <template #default="{ row }"
                                >#{{ row.prestamo_id }}</template
                            >
                        </el-table-column>
                        <el-table-column
                            label="Cuota"
                            width="120"
                            align="right"
                        >
                            <template #default="{ row }">
                                <span class="font-semibold tabular-nums">{{
                                    formatNumber(row.monto)
                                }}</span>
                            </template>
                        </el-table-column>
                        <el-table-column
                            v-if="puedeInformar"
                            label="Acciones"
                            width="150"
                            align="center"
                        >
                            <template #default="{ row }">
                                <el-button
                                    v-if="!row.informe_en_revision"
                                    size="small"
                                    type="success"
                                    plain
                                    @click="
                                        informarPago(row as CuotaPendienteRow)
                                    "
                                >
                                    Informar un pago
                                </el-button>
                                <span
                                    v-else
                                    class="text-muted-foreground text-xs"
                                    >Informe #{{
                                        row.informe_en_revision
                                    }}</span
                                >
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
                            @current-change="store.fetchList($event)"
                        />
                    </div>
                </template>
            </ResponsiveList>
        </div>

        <InformarPagoModal />
    </div>
</template>

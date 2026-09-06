<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';
import {
    type DetalleCuota,
    type DetallePrestamo,
    usePaymentReportStore,
} from '@/stores/paymentReport';

const store = usePaymentReportStore();
const { detalleOpen, detalleLoading, detalleRow, detallePrestamos } =
    storeToRefs(store);

const hoy = new Date().toISOString().slice(0, 10);

function seleccionadas(p: DetallePrestamo): number {
    return p.prestamos_dias.filter((d) => d.p_seleccionado).length;
}

function badgeClass(d: DetalleCuota): string {
    if (d.date === hoy) return 'bg-blue-600 text-white';
    if (d.festivo) return 'bg-purple-500 text-white';
    if (d.dom) return 'bg-red-600 text-white';
    return 'bg-green-500 text-white';
}

function amountClass(d: DetalleCuota): string {
    if (!d.apply) return 'text-gray-400';
    if (d.pagado) return 'text-green-700 font-semibold';
    if (d.date < hoy) return 'text-red-600 font-semibold';
    return 'text-indigo-600';
}

function clienteNombre(): string {
    const c = detalleRow.value?.cliente;
    if (!c) return '—';
    if (typeof c === 'string') {
        try {
            const o = JSON.parse(c || '{}') as {
                nombre?: string;
                apellido?: string;
            };
            return `${o.nombre ?? ''} ${o.apellido ?? ''}`.trim() || '—';
        } catch {
            return c;
        }
    }
    return `${c.nombre ?? ''} ${c.apellido ?? ''}`.trim() || '—';
}

const totalCuotasSel = computed(() =>
    detallePrestamos.value.reduce((a, p) => a + seleccionadas(p), 0),
);
</script>

<template>
    <el-dialog
        v-model="detalleOpen"
        :title="`Informe de pago #${detalleRow?.id ?? ''}`"
        width="min(860px, 95vw)"
        top="6vh"
    >
        <div
            v-if="detalleRow"
            class="text-muted-foreground mb-4 grid grid-cols-2 gap-2 text-sm md:grid-cols-4"
        >
            <div>
                <span class="font-semibold">Cliente:</span>
                {{ clienteNombre() }}
            </div>
            <div>
                <span class="font-semibold">Destino:</span>
                {{ detalleRow.destination_text }}
            </div>
            <div>
                <span class="font-semibold">Monto:</span>
                {{ formatNumber(detalleRow.value_amount) }}
            </div>
            <div>
                <span class="font-semibold">Estado:</span>
                {{ detalleRow.status_description?.desc ?? '—' }}
            </div>
        </div>

        <div v-loading="detalleLoading">
            <p class="mb-2 text-sm font-semibold">
                {{ detallePrestamos.length }} préstamo(s) informado(s) ·
                {{ totalCuotasSel }} cuota(s) seleccionada(s)
            </p>

            <el-empty
                v-if="!detalleLoading && detallePrestamos.length === 0"
                description="Sin cuotas registradas (informe de saldo a favor)"
            />

            <el-collapse v-else accordion>
                <el-collapse-item
                    v-for="p in detallePrestamos"
                    :key="p.id"
                    :name="p.id"
                >
                    <template #title>
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <span class="font-bold">Préstamo #{{ p.id }}</span>
                            <span class="text-muted-foreground">
                                Monto {{ formatNumber(p.monto_prestamo) }}
                            </span>
                            <el-tag size="small" type="primary">
                                {{ seleccionadas(p) }} cuota(s) seleccionada(s)
                            </el-tag>
                        </div>
                    </template>

                    <div
                        class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6"
                    >
                        <div
                            v-for="d in p.prestamos_dias"
                            :key="d.id"
                            class="flex h-11 overflow-hidden rounded-md border text-xs shadow-sm"
                            :class="[
                                d.apply ? '' : 'opacity-40',
                                d.p_seleccionado ? 'ring-2 ring-blue-500' : '',
                            ]"
                        >
                            <div
                                class="flex items-center px-2 text-center"
                                :class="badgeClass(d)"
                            >
                                <div>
                                    {{ d.sigla }}<br />{{ d.date.slice(5) }}
                                </div>
                            </div>
                            <div
                                class="flex flex-auto items-center justify-center px-1"
                            >
                                <span :class="amountClass(d)">
                                    {{ formatNumber(d.cuota) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </el-collapse-item>
            </el-collapse>
        </div>

        <template #footer>
            <el-button @click="store.cerrarDetalle()">Cerrar</el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { HandCoins, PiggyBank, ScrollText, Wallet } from '@lucide/vue';
import { storeToRefs } from 'pinia';
import ReporteEstadoBar from '@/components/reportes/ReporteEstadoBar.vue';
import ReporteKpiTile from '@/components/reportes/ReporteKpiTile.vue';
import { formatNumber } from '@/lib/format';
import { useReporteInformesPagoStore } from '@/stores/reporteInformesPago';

const store = useReporteInformesPagoStore();
const { resumen, loadingResumen } = storeToRefs(store);

/** Ícono por destino — cuotas vs. saldo a favor, no una identidad genérica. */
function iconoDestino(destination: number) {
    return destination === 1 ? HandCoins : PiggyBank;
}
</script>

<template>
    <div v-loading="loadingResumen" class="space-y-6">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <ReporteKpiTile
                label="Informes"
                :value="formatNumber(resumen?.totales.cantidad ?? 0)"
                :icon="ScrollText"
            />
            <ReporteKpiTile
                label="Monto recibido"
                :value="formatNumber(resumen?.totales.monto_total ?? 0)"
                :icon="Wallet"
            />
            <ReporteKpiTile
                v-for="d in resumen?.porDestino ?? []"
                :key="d.destination"
                :label="d.label"
                :value="formatNumber(d.monto)"
                :sublabel="`${formatNumber(d.cantidad)} informes`"
                :icon="iconoDestino(d.destination)"
            />
        </div>

        <div class="bg-card rounded-lg border p-4">
            <h3 class="text-foreground mb-4 text-sm font-semibold">
                Por estado
            </h3>
            <ReporteEstadoBar
                :data="resumen?.porEstado ?? []"
                footer-label="informes en total"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { AlertTriangle, HandCoins, TrendingUp, Wallet } from '@lucide/vue';
import { storeToRefs } from 'pinia';
import ReporteEstadoBar from '@/components/reportes/ReporteEstadoBar.vue';
import ReporteKpiTile from '@/components/reportes/ReporteKpiTile.vue';
import { formatNumber } from '@/lib/format';
import { useReportePrestamosStore } from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();
const { resumen, loadingResumen } = storeToRefs(store);
</script>

<template>
    <div v-loading="loadingResumen" class="space-y-6">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <ReporteKpiTile
                label="Préstamos"
                :value="formatNumber(resumen?.totales.cantidad ?? 0)"
                :icon="HandCoins"
            />
            <ReporteKpiTile
                label="Monto prestado"
                :value="formatNumber(resumen?.totales.monto_prestado ?? 0)"
                :icon="Wallet"
            />
            <ReporteKpiTile
                label="Utilidad"
                :value="formatNumber(resumen?.totales.utilidad ?? 0)"
                :icon="TrendingUp"
            />
            <ReporteKpiTile
                label="Monto perdido"
                :value="formatNumber(resumen?.totales.monto_perdido ?? 0)"
                tone="destructive"
                :icon="AlertTriangle"
            />
        </div>

        <div class="bg-card rounded-lg border p-4">
            <h3 class="text-foreground mb-4 text-sm font-semibold">
                Por estado
            </h3>
            <ReporteEstadoBar
                :data="resumen?.porEstado ?? []"
                footer-label="préstamos en total"
            />
        </div>
    </div>
</template>

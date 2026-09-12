<script setup lang="ts">
import { FileClock, TriangleAlert, Wallet } from '@lucide/vue';
import { Head } from '@inertiajs/vue3';
import CarteraPorEstadoChart, {
    type EstadoCartera,
} from '@/components/dashboard/CarteraPorEstadoChart.vue';
import SerieMensualChart, {
    type MesSerie,
} from '@/components/dashboard/SerieMensualChart.vue';
import { dashboard } from '@/routes';
import { formatNumber } from '@/lib/format';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    kpis: {
        cartera_activa: { cantidad: number; monto: number };
        cuotas_vencidas: { cantidad: number; monto: number };
        informes_por_revisar: number;
    };
    carteraPorEstado: EstadoCartera[];
    serieMensual: MesSerie[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <div class="flex items-center gap-2">
                    <Wallet class="text-muted-foreground size-4" />
                    <p class="text-muted-foreground text-sm">Cartera activa</p>
                </div>
                <p class="text-foreground mt-2 text-2xl font-semibold">
                    {{ formatNumber(kpis.cartera_activa.monto) }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                    {{ formatNumber(kpis.cartera_activa.cantidad) }} préstamos
                    pendientes
                </p>
            </div>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <div class="flex items-center gap-2">
                    <TriangleAlert class="text-destructive size-4" />
                    <p class="text-muted-foreground text-sm">Cuotas vencidas</p>
                </div>
                <p class="text-destructive mt-2 text-2xl font-semibold">
                    {{ formatNumber(kpis.cuotas_vencidas.monto) }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                    {{ formatNumber(kpis.cuotas_vencidas.cantidad) }} cuotas sin
                    cobrar
                </p>
            </div>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <div class="flex items-center gap-2">
                    <FileClock class="size-4" style="color: #0073c3" />
                    <p class="text-muted-foreground text-sm">
                        Informes por revisar
                    </p>
                </div>
                <p class="mt-2 text-2xl font-semibold" style="color: #0073c3">
                    {{ formatNumber(kpis.informes_por_revisar) }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                    Pagos informados a la espera de gestión
                </p>
            </div>
        </div>

        <div class="grid flex-1 gap-4 md:grid-cols-2">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <h2 class="text-foreground mb-4 text-sm font-medium">
                    Cartera por estado
                </h2>
                <CarteraPorEstadoChart :data="carteraPorEstado" />
            </div>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
            >
                <h2 class="text-foreground mb-4 text-sm font-medium">
                    Desembolsos vs. cobros (12 meses)
                </h2>
                <SerieMensualChart :data="serieMensual" />
            </div>
        </div>
    </div>
</template>

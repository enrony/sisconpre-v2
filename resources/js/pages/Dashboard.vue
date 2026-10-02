<script setup lang="ts">
import { ChevronRight, FileClock, TriangleAlert, Wallet } from '@lucide/vue';
import { Head, Link } from '@inertiajs/vue3';
import CarteraPorEstadoChart, {
    type EstadoCartera,
} from '@/components/dashboard/CarteraPorEstadoChart.vue';
import SerieMensualChart, {
    type MesSerie,
} from '@/components/dashboard/SerieMensualChart.vue';
import MisGruposCards from '@/components/grupos/MisGruposCards.vue';
import SolicitudesGrupoPanel from '@/components/grupos/SolicitudesGrupoPanel.vue';
import { dashboard } from '@/routes';
import { can } from '@/lib/can';
import { formatNumber } from '@/lib/format';
import type { SolicitudPorDecidir, TarjetaGrupo } from '@/types/grupos';

/** Mismos estados que cuenta el KPI (`DashboardController`): Pendiente y En revisión. */
const urlInformesPorRevisar = '/payment_report?estados=1,4';
const puedeVerInformes = can('payment_report.listar');

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
    solicitudesPorDecidir: SolicitudPorDecidir[];
    misGrupos: TarjetaGrupo[];
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <SolicitudesGrupoPanel :solicitudes="solicitudesPorDecidir" />

        <MisGruposCards :grupos="misGrupos" />

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

            <component
                :is="puedeVerInformes ? Link : 'div'"
                v-bind="puedeVerInformes ? { href: urlInformesPorRevisar } : {}"
                class="border-sidebar-border/70 dark:border-sidebar-border group rounded-xl border p-4"
                :class="
                    puedeVerInformes
                        ? 'hover:border-foreground/25 hover:bg-accent/40 focus-visible:ring-ring/50 transition-colors outline-none focus-visible:ring-[3px]'
                        : ''
                "
            >
                <div class="flex items-center gap-2">
                    <FileClock class="size-4" style="color: #0073c3" />
                    <p class="text-muted-foreground text-sm">
                        Informes por revisar
                    </p>
                    <ChevronRight
                        v-if="puedeVerInformes"
                        class="text-muted-foreground ml-auto size-4 transition-transform group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </div>
                <p class="mt-2 text-2xl font-semibold" style="color: #0073c3">
                    {{ formatNumber(kpis.informes_por_revisar) }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                    {{
                        puedeVerInformes
                            ? 'Pagos informados a la espera de gestión · ver listado'
                            : 'Pagos informados a la espera de gestión'
                    }}
                </p>
            </component>
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

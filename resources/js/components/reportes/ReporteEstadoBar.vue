<script setup lang="ts">
import { AlertTriangle, CircleCheck, CircleX, Clock } from '@lucide/vue';
import { computed, type Component } from 'vue';
import { formatNumber } from '@/lib/format';

/**
 * Desglose por estado en barra horizontal, reutilizable por cualquier
 * reporte: recibe ya resuelto un `tipo` semántico (info/success/warning/
 * danger) por fila — nunca colorea por una paleta categórica arbitraria,
 * porque un estado es un "status job" (escala fija, reservada), no una
 * identidad. Generalización de `dashboard/CarteraPorEstadoChart.vue` para
 * no atarse a los 4 estados puntuales de préstamos.
 */
export interface EstadoBreakdown {
    estatus: number;
    label: string;
    cantidad: number;
    tipo: 'info' | 'success' | 'warning' | 'danger';
}

const props = defineProps<{ data: EstadoBreakdown[]; footerLabel: string }>();

const TIPO_VISUAL: Record<
    EstadoBreakdown['tipo'],
    { icon: Component; barClass: string; textClass: string }
> = {
    info: {
        icon: Clock,
        barClass: 'bg-foreground/25',
        textClass: 'text-muted-foreground',
    },
    success: {
        icon: CircleCheck,
        barClass: 'bg-emerald-600',
        textClass: 'text-emerald-600 dark:text-emerald-400',
    },
    warning: {
        icon: AlertTriangle,
        barClass: 'bg-amber-500',
        textClass: 'text-amber-600 dark:text-amber-400',
    },
    danger: {
        icon: CircleX,
        barClass: 'bg-destructive',
        textClass: 'text-destructive',
    },
};

const filas = computed(() => {
    const max = Math.max(1, ...props.data.map((d) => d.cantidad));

    return props.data.map((d) => ({
        ...d,
        visual: TIPO_VISUAL[d.tipo] ?? TIPO_VISUAL.info,
        // Piso del 4% para que un valor bajo siga siendo visible.
        pct: Math.max(4, (d.cantidad / max) * 100),
    }));
});

const total = computed(() =>
    props.data.reduce((sum, d) => sum + d.cantidad, 0),
);
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="fila in filas"
            :key="fila.estatus"
            class="flex items-center gap-3"
        >
            <div class="flex w-32 shrink-0 items-center gap-1.5 text-sm">
                <component
                    :is="fila.visual.icon"
                    class="size-3.5 shrink-0"
                    :class="fila.visual.textClass"
                    aria-hidden="true"
                />
                <span class="text-foreground truncate">{{ fila.label }}</span>
            </div>
            <div class="bg-muted h-5 min-w-0 flex-1 overflow-hidden rounded-sm">
                <div
                    class="h-full rounded-sm transition-[width]"
                    :class="fila.visual.barClass"
                    :style="{ width: `${fila.pct}%` }"
                />
            </div>
            <span
                class="text-foreground w-12 shrink-0 text-right text-sm font-medium tabular-nums"
            >
                {{ formatNumber(fila.cantidad) }}
            </span>
        </div>

        <p v-if="!data.length" class="text-muted-foreground text-sm">
            Sin datos para los filtros aplicados.
        </p>
        <p v-else class="text-muted-foreground text-xs">
            {{ formatNumber(total) }} {{ footerLabel }}
        </p>
    </div>
</template>

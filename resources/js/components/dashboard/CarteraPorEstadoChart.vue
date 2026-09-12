<script setup lang="ts">
import { Ban, CircleCheck, CircleX, Clock } from '@lucide/vue';
import { computed, type Component } from 'vue';
import { formatNumber } from '@/lib/format';

export interface EstadoCartera {
    estatus: number;
    label: string;
    cantidad: number;
}

const props = defineProps<{ data: EstadoCartera[] }>();

/**
 * Pendiente/Pagado/Anulado/Perdido son estados, no series arbitrarias: usan
 * los tokens de estatus (bueno/advertencia/crítico), no una paleta
 * categórica. Pendiente es el estado "en curso" normal de la cartera, no una
 * alerta, así que se queda en tinta neutra en vez de un color de estatus.
 */
const ESTADO_VISUAL: Record<
    number,
    { icon: Component; barClass: string; textClass: string }
> = {
    1: {
        icon: Clock,
        barClass: 'bg-foreground/25',
        textClass: 'text-muted-foreground',
    },
    2: {
        icon: CircleCheck,
        barClass: 'bg-emerald-600',
        textClass: 'text-emerald-600 dark:text-emerald-400',
    },
    3: {
        icon: Ban,
        barClass: 'bg-amber-500',
        textClass: 'text-amber-600 dark:text-amber-400',
    },
    4: {
        icon: CircleX,
        barClass: 'bg-destructive',
        textClass: 'text-destructive',
    },
};

const filas = computed(() => {
    const max = Math.max(1, ...props.data.map((d) => d.cantidad));

    return props.data.map((d) => ({
        ...d,
        visual: ESTADO_VISUAL[d.estatus] ?? ESTADO_VISUAL[1],
        // Piso del 4% para que un valor bajo (1 de 60) siga siendo visible.
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
            <div class="flex w-28 shrink-0 items-center gap-1.5 text-sm">
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
                class="text-foreground w-10 shrink-0 text-right text-sm font-medium tabular-nums"
            >
                {{ formatNumber(fila.cantidad) }}
            </span>
        </div>

        <p class="text-muted-foreground text-xs">
            {{ formatNumber(total) }} préstamos en total
        </p>
    </div>
</template>

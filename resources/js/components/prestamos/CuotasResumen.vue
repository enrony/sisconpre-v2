<script setup lang="ts">
import { computed } from 'vue';
import type { CuotaDia } from '@/stores/prestamos';

/** Resumen del cronograma: conteo + barra de avance pagado / pendiente / vencido. */
const props = defineProps<{ dias: CuotaDia[] }>();

const HOY = new Date().toISOString().slice(0, 10);

const r = computed(() => {
    const aplicables = props.dias.filter((d) => d.apply);
    const total = aplicables.length;
    const pagadas = aplicables.filter((d) => d.pagado).length;
    const vencidas = aplicables.filter(
        (d) => !d.pagado && d.date.slice(0, 10) < HOY,
    ).length;
    const pendientes = total - pagadas;
    const base = total || 1;

    return {
        total,
        pagadas,
        pendientes,
        vencidas,
        pctPagadas: (pagadas / base) * 100,
        pctPendientes: ((pendientes - vencidas) / base) * 100,
        pctVencidas: (vencidas / base) * 100,
    };
});
</script>

<template>
    <div v-if="r.total" class="space-y-1.5">
        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-xs">
            <span class="text-foreground font-medium tabular-nums">
                {{ r.total }}
                <span class="text-muted-foreground font-normal">cuotas</span>
            </span>
            <span class="text-muted-foreground tabular-nums"
                >{{ r.pagadas }} pagadas</span
            >
            <span class="text-muted-foreground tabular-nums">
                {{ r.pendientes }} pendientes
            </span>
            <span
                v-if="r.vencidas"
                class="text-destructive font-medium tabular-nums"
            >
                {{ r.vencidas }} vencidas
            </span>
        </div>
        <div
            class="bg-muted flex h-1.5 overflow-hidden rounded-full"
            role="img"
            :aria-label="`${r.pagadas} pagadas, ${r.pendientes - r.vencidas} pendientes y ${r.vencidas} vencidas de ${r.total}`"
        >
            <div
                class="bg-emerald-600"
                :style="{ width: `${r.pctPagadas}%` }"
            />
            <div
                class="bg-foreground/20"
                :style="{ width: `${r.pctPendientes}%` }"
            />
            <div
                class="bg-destructive"
                :style="{ width: `${r.pctVencidas}%` }"
            />
        </div>
    </div>
</template>

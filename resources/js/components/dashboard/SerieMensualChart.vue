<script setup lang="ts">
import { LayoutGrid, Table2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatNumber } from '@/lib/format';

export interface MesSerie {
    mes: string; // 'YYYY-MM'
    desembolsado: number;
    cobrado: number;
}

const props = defineProps<{ data: MesSerie[] }>();

const MESES = [
    'ene',
    'feb',
    'mar',
    'abr',
    'may',
    'jun',
    'jul',
    'ago',
    'sep',
    'oct',
    'nov',
    'dic',
];

const WIDTH = 700;
const HEIGHT = 240;
const MARGIN = { top: 16, right: 12, bottom: 28, left: 56 };
const PLOT_W = WIDTH - MARGIN.left - MARGIN.right;
const PLOT_H = HEIGHT - MARGIN.top - MARGIN.bottom;

function niceMax(raw: number): number {
    if (raw <= 0) return 1;
    const magnitude = 10 ** Math.floor(Math.log10(raw));
    const residual = raw / magnitude;
    const niceResidual =
        residual <= 1 ? 1 : residual <= 2 ? 2 : residual <= 5 ? 5 : 10;

    return niceResidual * magnitude;
}

const maxValor = computed(() =>
    niceMax(
        Math.max(
            1,
            ...props.data.map((d) => d.desembolsado),
            ...props.data.map((d) => d.cobrado),
        ),
    ),
);

const yTicks = computed(() =>
    [0, 0.25, 0.5, 0.75, 1].map((f) => Math.round(maxValor.value * f)),
);

function x(i: number): number {
    const n = Math.max(1, props.data.length - 1);

    return MARGIN.left + (i / n) * PLOT_W;
}

function y(valor: number): number {
    return MARGIN.top + PLOT_H - (valor / maxValor.value) * PLOT_H;
}

function pathFor(serie: 'desembolsado' | 'cobrado'): string {
    return props.data
        .map((d, i) => `${i === 0 ? 'M' : 'L'}${x(i)},${y(d[serie])}`)
        .join(' ');
}

function mesLabel(mes: string): string {
    const idx = Number(mes.slice(5, 7)) - 1;

    return MESES[idx] ?? mes;
}

const hoveredIndex = ref<number | null>(null);

const hovered = computed(() =>
    hoveredIndex.value === null ? null : props.data[hoveredIndex.value],
);

const tooltipX = computed(() =>
    hoveredIndex.value === null ? 0 : x(hoveredIndex.value),
);

const tooltipAlignEnd = computed(() => tooltipX.value > WIDTH * 0.65);

const ultimo = computed(() => props.data.at(-1));

const modoTabla = ref(false);
</script>

<template>
    <div class="viz-root space-y-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block h-0.5 w-3.5 rounded-full"
                        style="background: var(--series-desembolsado)"
                    />
                    <span class="text-foreground">Desembolsado</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="inline-block h-0.5 w-3.5 rounded-full"
                        style="background: var(--series-cobrado)"
                    />
                    <span class="text-foreground">Cobrado</span>
                </span>
            </div>
            <button
                type="button"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                @click="modoTabla = !modoTabla"
            >
                <component
                    :is="modoTabla ? LayoutGrid : Table2"
                    class="size-3.5"
                />
                {{ modoTabla ? 'Ver gráfico' : 'Ver tabla' }}
            </button>
        </div>

        <div v-if="!modoTabla" class="relative">
            <svg
                :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
                class="w-full"
                role="img"
                aria-label="Tendencia mensual de montos desembolsados y cobrados"
            >
                <line
                    v-for="tick in yTicks"
                    :key="tick"
                    :x1="MARGIN.left"
                    :x2="WIDTH - MARGIN.right"
                    :y1="y(tick)"
                    :y2="y(tick)"
                    class="grid-line"
                />
                <text
                    v-for="tick in yTicks"
                    :key="`label-${tick}`"
                    :x="MARGIN.left - 8"
                    :y="y(tick)"
                    text-anchor="end"
                    dominant-baseline="middle"
                    class="axis-label"
                >
                    {{ formatNumber(tick) }}
                </text>

                <text
                    v-for="(d, i) in data"
                    :key="d.mes"
                    :x="x(i)"
                    :y="HEIGHT - 8"
                    text-anchor="middle"
                    class="axis-label"
                >
                    {{ mesLabel(d.mes) }}
                </text>

                <line
                    v-if="hovered"
                    :x1="tooltipX"
                    :x2="tooltipX"
                    :y1="MARGIN.top"
                    :y2="HEIGHT - MARGIN.bottom"
                    class="crosshair"
                />

                <path
                    :d="pathFor('desembolsado')"
                    fill="none"
                    stroke="var(--series-desembolsado)"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />
                <path
                    :d="pathFor('cobrado')"
                    fill="none"
                    stroke="var(--series-cobrado)"
                    stroke-width="2"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                <template v-if="ultimo">
                    <circle
                        :cx="x(data.length - 1)"
                        :cy="y(ultimo.desembolsado)"
                        r="4"
                        fill="var(--series-desembolsado)"
                        class="end-dot"
                    />
                    <circle
                        :cx="x(data.length - 1)"
                        :cy="y(ultimo.cobrado)"
                        r="4"
                        fill="var(--series-cobrado)"
                        class="end-dot"
                    />
                </template>

                <circle
                    v-if="hovered"
                    :cx="tooltipX"
                    :cy="y(hovered.desembolsado)"
                    r="4"
                    fill="var(--series-desembolsado)"
                    class="end-dot"
                />
                <circle
                    v-if="hovered"
                    :cx="tooltipX"
                    :cy="y(hovered.cobrado)"
                    r="4"
                    fill="var(--series-cobrado)"
                    class="end-dot"
                />
            </svg>

            <div
                class="hit-layer"
                :style="{
                    left: `${(MARGIN.left / WIDTH) * 100}%`,
                    right: `${(MARGIN.right / WIDTH) * 100}%`,
                    top: `${(MARGIN.top / HEIGHT) * 100}%`,
                    bottom: `${(MARGIN.bottom / HEIGHT) * 100}%`,
                }"
                @mouseleave="hoveredIndex = null"
            >
                <button
                    v-for="(d, i) in data"
                    :key="d.mes"
                    type="button"
                    class="hit-col"
                    :aria-label="`${mesLabel(d.mes)}: desembolsado ${formatNumber(d.desembolsado)}, cobrado ${formatNumber(d.cobrado)}`"
                    @mouseenter="hoveredIndex = i"
                    @focus="hoveredIndex = i"
                    @blur="hoveredIndex = null"
                />
            </div>

            <div
                v-if="hovered"
                class="tooltip"
                :class="{ 'tooltip--end': tooltipAlignEnd }"
                :style="{ left: `${(tooltipX / WIDTH) * 100}%` }"
            >
                <p class="text-foreground text-xs font-medium capitalize">
                    {{ mesLabel(hovered.mes) }}
                </p>
                <p class="flex items-center justify-between gap-3 text-xs">
                    <span class="text-muted-foreground">Desembolsado</span>
                    <span class="text-foreground tabular-nums">{{
                        formatNumber(hovered.desembolsado)
                    }}</span>
                </p>
                <p class="flex items-center justify-between gap-3 text-xs">
                    <span class="text-muted-foreground">Cobrado</span>
                    <span class="text-foreground tabular-nums">{{
                        formatNumber(hovered.cobrado)
                    }}</span>
                </p>
            </div>
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr
                    class="text-muted-foreground border-border border-b text-left"
                >
                    <th class="py-1.5 font-normal">Mes</th>
                    <th class="py-1.5 text-right font-normal">Desembolsado</th>
                    <th class="py-1.5 text-right font-normal">Cobrado</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="d in data"
                    :key="d.mes"
                    class="border-border/60 border-b last:border-0"
                >
                    <td class="text-foreground py-1.5 capitalize">
                        {{ mesLabel(d.mes) }}
                    </td>
                    <td class="text-foreground py-1.5 text-right tabular-nums">
                        {{ formatNumber(d.desembolsado) }}
                    </td>
                    <td class="text-foreground py-1.5 text-right tabular-nums">
                        {{ formatNumber(d.cobrado) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.viz-root {
    --series-desembolsado: #0073c3;
    --series-cobrado: #eb6834;
}

.dark .viz-root {
    --series-cobrado: #d95926;
}

.grid-line {
    stroke: var(--color-border);
    stroke-width: 1;
}

.axis-label {
    fill: var(--color-muted-foreground);
    font-size: 10px;
}

.crosshair {
    stroke: var(--color-muted-foreground);
    stroke-width: 1;
    opacity: 0.4;
}

.end-dot {
    stroke: var(--color-background);
    stroke-width: 2;
}

.hit-layer {
    position: absolute;
    display: flex;
}

.hit-col {
    flex: 1;
    height: 100%;
    background: transparent;
    border: 0;
    padding: 0;
    cursor: crosshair;
}

.tooltip {
    position: absolute;
    top: 0.5rem;
    transform: translateX(-50%);
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    padding: 0.5rem 0.625rem;
    min-width: 9.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    pointer-events: none;
}

.tooltip--end {
    transform: translateX(-100%);
}
</style>

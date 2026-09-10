<script setup lang="ts">
import { Check, Pencil, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { formatNumber } from '@/lib/format';

/**
 * Cronograma de cuotas de un préstamo. Único componente para las dos vistas:
 * el detalle de la lista (`CuotaDia`, sólo lectura) y el calendario del
 * asistente de alta (`Cuota`, editable — clic para mover la fecha).
 */
interface DiaCuota {
    id?: number | string;
    date: string;
    sigla: string;
    cuota: number | string;
    apply: boolean;
    dom?: boolean;
    festivo?: boolean;
    pagado?: boolean;
    /** Días transcurridos desde la fecha (positivo = atrasada). Sólo asistente. */
    diff?: number;
    /** La fecha fue movida a mano en el asistente. */
    date_change?: boolean;
}

const props = defineProps<{
    dias: DiaCuota[];
    editable?: boolean;
}>();

const emit = defineEmits<{ (e: 'editDate', index: number): void }>();

type Estado = 'na' | 'pagada' | 'hoy' | 'vencida' | 'proxima';

const HOY = new Date().toISOString().slice(0, 10);
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

function estado(d: DiaCuota): Estado {
    if (!d.apply) return 'na';
    if (d.pagado) return 'pagada';

    const iso = d.date.slice(0, 10);
    if (d.diff === 0 || iso === HOY) return 'hoy';

    const vencida = typeof d.diff === 'number' ? d.diff > 0 : iso < HOY;
    return vencida ? 'vencida' : 'proxima';
}

function fechaCorta(d: DiaCuota): string {
    const iso = d.date.slice(0, 10);
    return `${Number(iso.slice(8, 10))} ${MESES[Number(iso.slice(5, 7)) - 1] ?? ''}`;
}

const filas = computed(() =>
    props.dias.map((d, index) => ({
        d,
        index,
        estado: estado(d),
        fecha: fechaCorta(d),
    })),
);

function onActivate(index: number, estadoFila: Estado): void {
    if (props.editable && estadoFila !== 'na') {
        emit('editDate', index);
    }
}
</script>

<template>
    <ol
        v-if="filas.length"
        class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3"
    >
        <template v-for="fila in filas" :key="fila.d.id ?? fila.index">
            <li
                v-if="fila.estado === 'na'"
                class="text-muted-foreground col-span-full flex items-center gap-2 px-1 py-0.5 text-[11px]"
            >
                <span class="bg-border h-px flex-1" />
                <span class="tabular-nums">
                    {{ fila.d.sigla.toLowerCase() }} {{ fila.fecha
                    }}{{ fila.d.festivo ? ' · feriado' : '' }} · no aplica
                </span>
                <span class="bg-border h-px flex-1" />
            </li>

            <li
                v-else
                class="bg-card flex justify-center rounded-lg border px-3 py-2 text-xs"
                :class="[
                    fila.estado === 'hoy'
                        ? 'border-foreground/40'
                        : 'border-border',
                    editable
                        ? 'hover:border-foreground/30 hover:bg-accent focus-visible:outline-ring cursor-pointer transition-colors focus-visible:outline-2 focus-visible:outline-offset-2'
                        : '',
                ]"
                :tabindex="editable ? 0 : undefined"
                :role="editable ? 'button' : undefined"
                :aria-label="
                    editable
                        ? `Cambiar la fecha de la cuota del ${fila.fecha}`
                        : undefined
                "
                @click="onActivate(fila.index, fila.estado)"
                @keydown.enter.prevent="onActivate(fila.index, fila.estado)"
                @keydown.space.prevent="onActivate(fila.index, fila.estado)"
            >
                <div class="flex w-full max-w-[15rem] items-center gap-3">
                    <div
                        class="text-muted-foreground flex min-w-[3.5rem] flex-col text-[11px] leading-tight"
                    >
                        <span
                            class="flex items-center gap-1 font-medium tracking-wide uppercase"
                            :class="
                                fila.estado === 'hoy' ? 'text-foreground' : ''
                            "
                        >
                            {{ fila.d.sigla }}
                            <Pencil
                                v-if="fila.d.date_change"
                                class="text-muted-foreground size-3"
                                aria-label="Fecha modificada"
                            />
                        </span>
                        <span
                            class="tabular-nums"
                            :class="
                                fila.estado === 'hoy'
                                    ? 'text-foreground/80'
                                    : ''
                            "
                        >
                            {{ fila.fecha }}
                        </span>
                    </div>

                    <div class="flex flex-1 items-center justify-end gap-1.5">
                        <Check
                            v-if="fila.estado === 'pagada'"
                            class="size-3.5 text-emerald-600"
                            aria-label="Pagada"
                        />
                        <TriangleAlert
                            v-else-if="fila.estado === 'vencida'"
                            class="text-destructive size-3.5"
                            aria-label="Vencida"
                        />
                        <span
                            v-else-if="fila.estado === 'hoy'"
                            class="bg-foreground text-background rounded px-1.5 py-px text-[10px] font-medium tracking-wide uppercase"
                        >
                            hoy
                        </span>
                        <span
                            class="text-sm tabular-nums"
                            :class="{
                                'text-muted-foreground font-medium line-through':
                                    fila.estado === 'pagada',
                                'text-destructive font-semibold':
                                    fila.estado === 'vencida',
                                'text-foreground font-semibold':
                                    fila.estado === 'hoy' ||
                                    fila.estado === 'proxima',
                            }"
                        >
                            {{ formatNumber(fila.d.cuota) }}
                        </span>
                    </div>
                </div>
            </li>
        </template>
    </ol>

    <p
        v-else
        class="border-border text-muted-foreground rounded-lg border border-dashed px-3 py-6 text-center text-sm"
    >
        Este préstamo no tiene cuotas registradas.
    </p>
</template>

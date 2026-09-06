<script setup lang="ts">
import { formatNumber } from '@/lib/format';
import type { CuotaDia } from '@/stores/prestamos';

defineProps<{ dias: CuotaDia[] }>();

const hoy = new Date().toISOString().slice(0, 10);

function badgeClass(d: CuotaDia): string {
    if (d.date === hoy) return 'bg-blue-600 text-white';
    if (d.festivo) return 'bg-purple-500 text-white';
    if (d.dom) return 'bg-red-600 text-white';
    return 'bg-green-500 text-white';
}

function amountClass(d: CuotaDia): string {
    if (!d.apply) return 'text-gray-400';
    if (d.pagado) return 'text-green-700 font-semibold';
    if (d.date < hoy) return 'text-red-600 font-semibold';
    return 'text-indigo-600';
}
</script>

<template>
    <div class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6">
        <div
            v-for="d in dias"
            :key="d.id"
            class="flex h-11 overflow-hidden rounded-md border text-xs shadow-sm"
            :class="d.apply ? '' : 'opacity-40'"
        >
            <div
                class="flex items-center px-2 text-center"
                :class="badgeClass(d)"
            >
                <div>{{ d.sigla }}<br />{{ d.date.slice(5) }}</div>
            </div>
            <div class="flex flex-auto items-center justify-center px-1">
                <span :class="amountClass(d)">{{ formatNumber(d.cuota) }}</span>
            </div>
        </div>
    </div>
</template>

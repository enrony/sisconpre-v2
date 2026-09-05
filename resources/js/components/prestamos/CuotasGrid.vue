<script setup lang="ts">
import { formatNumber } from '@/lib/format';
import type { Cuota } from '@/lib/prestamoSchedule';

defineProps<{ lista: Cuota[] }>();

function badgeClass(c: Cuota): string {
    if (c.diff === 0) return 'bg-blue-600 text-white';
    if (c.festivo) return 'bg-purple-500 text-white';
    if (c.dom) return 'bg-red-600 text-white';
    return 'bg-green-500 text-white';
}
</script>

<template>
    <div
        v-if="lista.length"
        class="grid grid-cols-2 gap-2 rounded-md p-3 shadow md:grid-cols-4 lg:grid-cols-5"
    >
        <div
            v-for="(c, i) in lista"
            :key="`cuota-${i}`"
            class="flex h-12 overflow-hidden rounded-md border text-xs font-bold shadow-sm"
            :class="c.apply ? 'opacity-100' : 'opacity-40'"
        >
            <div
                class="flex items-center px-2 text-center"
                :class="badgeClass(c)"
            >
                <div>{{ c.sigla }}<br />{{ c.date.slice(5) }}</div>
            </div>
            <div
                class="flex flex-auto flex-col items-center justify-center px-1"
            >
                <span v-if="c.apply" :class="c.textColorCuotas">
                    {{ formatNumber(c.cuota) }}
                </span>
                <span
                    v-if="c.apply && c.diff > 0"
                    class="text-[10px] text-red-500"
                >
                    {{ c.diff }} día(s)
                </span>
                <span
                    v-else-if="c.apply && c.diff === 0"
                    class="text-[10px] text-blue-600"
                >
                    Hoy
                </span>
            </div>
        </div>
    </div>
</template>

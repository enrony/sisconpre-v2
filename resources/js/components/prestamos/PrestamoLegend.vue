<script setup lang="ts">
import { computed } from 'vue';
import type { CuotaDia } from '@/stores/prestamos';

const props = defineProps<{ dias: CuotaDia[] }>();

const hoy = new Date().toISOString().slice(0, 10);

const aplicables = computed(() => props.dias.filter((d) => d.apply));
const pagadas = computed(() => aplicables.value.filter((d) => d.pagado));
const pendientes = computed(() => aplicables.value.filter((d) => !d.pagado));
const demoradas = computed(
    () => aplicables.value.filter((d) => !d.pagado && d.date < hoy).length,
);
</script>

<template>
    <div class="flex flex-wrap gap-3 text-xs">
        <span
            class="rounded-md bg-yellow-200 px-2 py-1 font-semibold text-yellow-900"
        >
            Cuotas: {{ aplicables.length }}
        </span>
        <span
            class="rounded-md bg-green-500 px-2 py-1 font-semibold text-white"
        >
            Pagadas: {{ pagadas.length }}
        </span>
        <span
            class="rounded-md bg-indigo-500 px-2 py-1 font-semibold text-white"
        >
            Pendientes: {{ pendientes.length }}
        </span>
        <span class="rounded-md bg-red-500 px-2 py-1 font-semibold text-white">
            Demoradas: {{ demoradas }}
        </span>
    </div>
</template>

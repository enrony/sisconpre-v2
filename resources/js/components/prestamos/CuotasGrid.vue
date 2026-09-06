<script setup lang="ts">
import { ElNotification } from 'element-plus';
import { computed, ref } from 'vue';
import { formatNumber } from '@/lib/format';
import type { Cuota } from '@/lib/prestamoSchedule';
import { usePrestamosStore } from '@/stores/prestamos';

const props = defineProps<{ lista: Cuota[]; editable?: boolean }>();

const store = usePrestamosStore();

const modalOpen = ref(false);
const editIndex = ref(-1);
const nuevaFecha = ref('');

const cuotaEnEdicion = computed(() =>
    editIndex.value >= 0 ? props.lista[editIndex.value] : null,
);

function badgeClass(c: Cuota): string {
    if (c.diff === 0) return 'bg-blue-600 text-white';
    if (c.festivo) return 'bg-purple-500 text-white';
    if (c.dom) return 'bg-red-600 text-white';
    return 'bg-green-500 text-white';
}

function abrirCambioFecha(i: number) {
    if (!props.editable || !props.lista[i].apply) return;
    editIndex.value = i;
    nuevaFecha.value = props.lista[i].date;
    modalOpen.value = true;
}

function guardar() {
    const err = store.cambiarFechaCuota(editIndex.value, nuevaFecha.value);
    if (err) {
        ElNotification.error(err);
        return;
    }
    ElNotification.success('Fecha de la cuota actualizada');
    modalOpen.value = false;
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
            :class="[
                c.apply ? 'opacity-100' : 'opacity-40',
                editable && c.apply
                    ? 'cursor-pointer hover:ring-2 hover:ring-blue-300'
                    : '',
            ]"
            @click="abrirCambioFecha(i)"
        >
            <div
                class="flex items-center px-2 text-center"
                :class="badgeClass(c)"
            >
                <div>
                    {{ c.sigla }}<br />{{ c.date.slice(5)
                    }}<span v-if="c.date_change" title="Fecha modificada">
                        *</span
                    >
                </div>
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

    <el-dialog
        v-model="modalOpen"
        title="Cambiar fecha de la cuota"
        width="min(420px, 92vw)"
        append-to-body
    >
        <div v-if="cuotaEnEdicion" class="space-y-2 text-sm">
            <div>
                Fecha actual: <b>{{ cuotaEnEdicion.date }}</b>
            </div>
            <div>
                Importe: <b>{{ formatNumber(cuotaEnEdicion.cuota) }}</b>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Nueva fecha</label
                >
                <el-date-picker
                    v-model="nuevaFecha"
                    type="date"
                    class="!w-full"
                    value-format="YYYY-MM-DD"
                    :clearable="false"
                />
            </div>
        </div>
        <template #footer>
            <el-button @click="modalOpen = false">Cancelar</el-button>
            <el-button type="primary" @click="guardar">Cambiar fecha</el-button>
        </template>
    </el-dialog>
</template>

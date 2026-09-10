<script setup lang="ts">
import { computed, ref } from 'vue';
import CuotasSchedule from '@/components/prestamos/CuotasSchedule.vue';
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

function abrirCambioFecha(i: number) {
    if (!props.editable || !props.lista[i]?.apply) return;
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
    <CuotasSchedule
        :dias="lista"
        :editable="editable"
        @edit-date="abrirCambioFecha"
    />

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
                <label class="text-muted-foreground text-xs font-semibold">
                    Nueva fecha
                </label>
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

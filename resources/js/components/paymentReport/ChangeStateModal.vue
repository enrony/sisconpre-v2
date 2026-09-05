<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { ElNotification } from 'element-plus';
import { computed, ref, watch } from 'vue';
import {
    type EstadoMovimiento,
    type InformePagoRow,
    usePaymentReportStore,
} from '@/stores/paymentReport';

const props = defineProps<{ open: boolean; row: InformePagoRow | null }>();
const emit = defineEmits<{ 'update:open': [boolean] }>();

const store = usePaymentReportStore();
const { estados } = storeToRefs(store);

const estadoActualId = computed(() => props.row?.status_description?.id ?? 0);
const estadoActualDesc = computed(
    () => props.row?.status_description?.desc ?? '—',
);
const finalizado = computed(() =>
    Boolean(props.row?.status_description?.finish_estatus),
);

const opciones = computed<EstadoMovimiento[]>(() =>
    estados.value.filter((e) => e.id !== estadoActualId.value),
);

const seleccion = ref<number | null>(null);
const motivo = ref('');
const procesando = ref(false);

const estadoElegido = computed(() =>
    estados.value.find((e) => e.id === seleccion.value),
);
const requiereMotivo = computed(() => Boolean(estadoElegido.value?.motivo));

watch(
    () => props.open,
    (v) => {
        if (v) {
            seleccion.value = null;
            motivo.value = '';
        }
    },
);

function cerrar() {
    emit('update:open', false);
}

async function procesar() {
    if (!props.row || !seleccion.value) return;
    if (requiereMotivo.value && !motivo.value.trim()) {
        ElNotification.warning('Indique el motivo del cambio de estado.');
        return;
    }

    procesando.value = true;
    try {
        const res = await store.changeState({
            id: props.row.id,
            estatusActual: estadoActualId.value,
            estatusSelected: seleccion.value,
            motivo: requiereMotivo.value ? motivo.value.trim() : null,
        });

        if (res.success) {
            ElNotification.success(res.message ?? 'Estado actualizado');
            cerrar();
            await store.fetchList(store.lista?.current_page ?? 1);
        } else {
            ElNotification.error(res.message ?? 'No se pudo cambiar el estado');
        }
    } catch {
        ElNotification.error('Error al cambiar el estado');
    } finally {
        procesando.value = false;
    }
}
</script>

<template>
    <el-dialog
        :model-value="open"
        title="Cambiar estado del informe"
        width="min(460px, 92vw)"
        :close-on-click-modal="false"
        @update:model-value="emit('update:open', $event)"
    >
        <div v-if="row" class="space-y-3">
            <div class="text-sm">
                Informe <b>#{{ row.id }}</b> — estado actual:
                <b>{{ estadoActualDesc }}</b>
            </div>

            <el-alert
                v-if="finalizado"
                type="info"
                :closable="false"
                title="Este informe está en un estado final y no se puede cambiar."
            />

            <template v-else>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Nuevo estado</label
                    >
                    <el-select
                        v-model="seleccion"
                        class="w-full"
                        placeholder="Seleccione la acción"
                    >
                        <el-option
                            v-for="e in opciones"
                            :key="e.id"
                            :label="e.action_description"
                            :value="e.id"
                        />
                    </el-select>
                </div>

                <div v-if="requiereMotivo">
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Motivo</label
                    >
                    <el-input
                        v-model="motivo"
                        type="textarea"
                        :rows="3"
                        placeholder="Indique el motivo"
                    />
                </div>
            </template>
        </div>

        <template #footer>
            <el-button @click="cerrar">Cancelar</el-button>
            <el-button
                v-if="!finalizado"
                type="primary"
                :loading="procesando"
                :disabled="!seleccion"
                @click="procesar"
            >
                Procesar
            </el-button>
        </template>
    </el-dialog>
</template>

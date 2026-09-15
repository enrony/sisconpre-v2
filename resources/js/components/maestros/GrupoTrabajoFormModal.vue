<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Copy, RefreshCw } from '@lucide/vue';
import { ElMessage } from 'element-plus';
import { computed, reactive, ref, watch } from 'vue';
import type {
    MaestroConfig,
    MaestroRow,
    MaestroTables,
} from '@/components/maestros/types';
import http from '@/lib/http';

/**
 * Modal de creación/edición de "Grupo de trabajo" — no usa el
 * MaestroFormModal genérico porque el campo "Código" necesita una UX propia
 * (autogenerado, mostrado en grande para copiar/compartir, con botón de
 * regenerar). El resto de los ~14 maestros no la necesita y sigue usando el
 * modal genérico sin cambios.
 */
const props = defineProps<{
    config: MaestroConfig;
    open: boolean;
    record: MaestroRow | null;
    currentPage: number;
    tables?: MaestroTables;
}>();

const emit = defineEmits<{ 'update:open': [boolean]; saved: [] }>();

const page = usePage();
const submitting = ref(false);
const generandoCodigo = ref(false);

const editando = computed(() => Boolean(props.record?.id));
const titulo = computed(
    () => `${editando.value ? 'Editar' : 'Nuevo'} grupo de trabajo`,
);

const form = reactive<{
    id: number;
    nombre: string;
    city_id: number | string | null;
    code: string;
}>({ id: 0, nombre: '', city_id: null, code: '' });

const ciudades = computed(() => props.tables?.CitiesAll ?? []);

const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);

async function generarCodigo() {
    generandoCodigo.value = true;
    try {
        const { data } = await http.get<{ code: string }>(
            '/grupos_trabajo/generateCode',
        );
        form.code = data.code;
    } finally {
        generandoCodigo.value = false;
    }
}

function reset() {
    form.id = props.record?.id ?? 0;
    form.nombre = (props.record?.nombre as string | undefined) ?? '';
    form.city_id =
        (props.record?.city_id as number | string | null | undefined) ?? null;
    form.code = (props.record?.code as string | undefined) ?? '';

    if (!editando.value) {
        void generarCodigo();
    }
}

watch(
    () => props.open,
    (v) => {
        if (v) reset();
    },
);

async function copiarCodigo() {
    if (!form.code) return;
    try {
        await navigator.clipboard.writeText(form.code);
        ElMessage.success('Código copiado');
    } catch {
        // Portapapeles no disponible (contexto no seguro, permisos, etc.):
        // el código sigue visible en pantalla para copiarlo a mano.
    }
}

function cerrar() {
    emit('update:open', false);
}

function guardar() {
    submitting.value = true;
    router.put(
        `/${props.config.resource}`,
        { ...form, paginaActual: props.currentPage },
        {
            preserveScroll: true,
            onSuccess: () => {
                cerrar();
                emit('saved');
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <el-dialog
        :model-value="open"
        :title="titulo"
        width="min(480px, 92vw)"
        :close-on-click-modal="false"
        @update:model-value="emit('update:open', $event)"
    >
        <el-form label-position="top" @submit.prevent="guardar">
            <el-form-item label="Nombre" :error="errors.nombre">
                <el-input v-model="form.nombre" />
            </el-form-item>

            <el-form-item label="Ciudad" :error="errors.city_id">
                <el-select
                    v-model="form.city_id"
                    class="w-full"
                    filterable
                    clearable
                    placeholder="Seleccione una ciudad"
                >
                    <el-option
                        v-for="c in ciudades"
                        :key="c.id"
                        :label="c.nombre as string"
                        :value="c.id!"
                    />
                </el-select>
            </el-form-item>

            <el-form-item label="Código">
                <div
                    class="flex w-full items-center gap-2.5 rounded-md px-3 py-2.5 text-white"
                    style="background: #0073c3"
                >
                    <el-tooltip content="Generar un código nuevo">
                        <button
                            type="button"
                            class="shrink-0 text-white/85 hover:text-white disabled:opacity-40"
                            :disabled="generandoCodigo"
                            @click="generarCodigo"
                        >
                            <RefreshCw
                                class="size-4"
                                :class="{ 'animate-spin': generandoCodigo }"
                            />
                        </button>
                    </el-tooltip>
                    <span
                        class="flex-1 text-center font-mono text-xl font-semibold tracking-[0.3em]"
                    >
                        {{ form.code || '······' }}
                    </span>
                    <el-tooltip content="Copiar código">
                        <button
                            type="button"
                            class="shrink-0 text-white/85 hover:text-white disabled:opacity-40"
                            :disabled="!form.code"
                            @click="copiarCodigo"
                        >
                            <Copy class="size-4" />
                        </button>
                    </el-tooltip>
                </div>
                <p class="text-muted-foreground mt-1.5 text-xs">
                    Un usuario nuevo lo ingresa al registrarse para unirse a
                    este grupo (y heredar su país) — compartilo por correo o
                    WhatsApp.
                </p>
            </el-form-item>
        </el-form>

        <template #footer>
            <el-button @click="cerrar">Cancelar</el-button>
            <el-button type="primary" :loading="submitting" @click="guardar">
                Guardar
            </el-button>
        </template>
    </el-dialog>
</template>

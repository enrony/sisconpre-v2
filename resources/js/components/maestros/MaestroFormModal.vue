<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import type {
    MaestroConfig,
    MaestroField,
    MaestroRow,
    MaestroTables,
} from '@/components/maestros/types';

const props = defineProps<{
    config: MaestroConfig;
    open: boolean;
    record: MaestroRow | null;
    currentPage: number;
    tables?: MaestroTables;
}>();

function opciones(
    f: MaestroField,
): { label: string; value: string | number }[] {
    if (f.optionsKey) {
        return (props.tables?.[f.optionsKey] ?? []).map((o) => ({
            label: String(o[f.optionLabel ?? 'description'] ?? o.id),
            value: (o[f.optionValue ?? 'id'] ?? '') as string | number,
        }));
    }
    return f.options ?? [];
}

const emit = defineEmits<{ 'update:open': [boolean]; saved: [] }>();

const page = usePage();
const submitting = ref(false);

const editando = computed(() => Boolean(props.record?.id));
const titulo = computed(
    () => `${editando.value ? 'Editar' : 'Nuevo'} ${props.config.singular}`,
);

const form = reactive<Record<string, unknown>>({ id: 0 });

function reset() {
    for (const key of Object.keys(form)) {
        delete form[key];
    }
    form.id = props.record?.id ?? 0;
    for (const f of props.config.fields) {
        form[f.key] =
            props.record?.[f.key] ??
            (f.type === 'switch' ? false : f.type === 'number' ? 0 : '');
        if (f.type === 'date' && !form[f.key]) form[f.key] = '';
    }
}

watch(
    () => props.open,
    (v) => {
        if (v) reset();
    },
);

const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);

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
        width="min(520px, 92vw)"
        :close-on-click-modal="false"
        @update:model-value="emit('update:open', $event)"
    >
        <el-form label-position="top" @submit.prevent="guardar">
            <div class="grid grid-cols-2 gap-3">
                <el-form-item
                    v-for="f in config.fields"
                    :key="f.key"
                    :label="f.label"
                    :error="errors[f.key]"
                    :class="(f.span ?? 2) === 1 ? 'col-span-1' : 'col-span-2'"
                >
                    <el-switch
                        v-if="f.type === 'switch'"
                        v-model="form[f.key] as boolean"
                    />
                    <el-input-number
                        v-else-if="f.type === 'number'"
                        v-model="form[f.key] as number"
                        controls-position="right"
                        class="!w-full"
                    />
                    <el-date-picker
                        v-else-if="f.type === 'date'"
                        v-model="form[f.key] as string"
                        type="date"
                        value-format="YYYY-MM-DD"
                        format="YYYY-MM-DD"
                        class="!w-full"
                    />
                    <el-select
                        v-else-if="f.type === 'select'"
                        v-model="form[f.key] as string | number"
                        class="w-full"
                        filterable
                        clearable
                    >
                        <el-option
                            v-for="o in opciones(f)"
                            :key="String(o.value)"
                            :label="o.label"
                            :value="o.value"
                        />
                    </el-select>
                    <el-input v-else v-model="form[f.key] as string" />
                </el-form-item>
            </div>
        </el-form>

        <template #footer>
            <el-button @click="cerrar">Cancelar</el-button>
            <el-button type="primary" :loading="submitting" @click="guardar">
                Guardar
            </el-button>
        </template>
    </el-dialog>
</template>

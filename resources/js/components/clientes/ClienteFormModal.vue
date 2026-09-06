<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { ElNotification } from 'element-plus';
import { computed } from 'vue';
import { useClientesStore } from '@/stores/clientes';

const props = defineProps<{ paginaActual: number }>();

const page = usePage();
const store = useClientesStore();
const {
    modalOpen,
    submitting,
    editing,
    form,
    countries,
    departments,
    cities,
    tiposDocumento,
} = storeToRefs(store);

const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);
const titulo = computed(() =>
    editing.value ? 'Editar cliente' : 'Registrar nuevo cliente',
);

const tiposDelPais = computed(() =>
    tiposDocumento.value.filter((t) => t.country_id === form.value.country_id),
);
const departamentosDelPais = computed(() =>
    departments.value.filter((d) => d.country_id === form.value.country_id),
);
const ciudadesDelDepto = computed(() =>
    cities.value.filter((c) => c.department_id === form.value.department_id),
);

function onCountry() {
    form.value.department_id = null;
    form.value.city_id = null;
    form.value.idtipo_documento = null;
}

const valido = computed(
    () =>
        !!form.value.idtipo_documento &&
        form.value.documento.trim() !== '' &&
        form.value.nombre.trim() !== '' &&
        form.value.telefono.trim() !== '' &&
        form.value.direccion.trim() !== '' &&
        /.+@.+\..+/.test(form.value.email) &&
        !!form.value.city_id,
);

function guardar() {
    if (!valido.value) {
        ElNotification.warning('Complete los campos obligatorios.');
        return;
    }
    store.guardar(props.paginaActual);
}
</script>

<template>
    <el-dialog
        v-model="modalOpen"
        :title="titulo"
        width="min(720px, 94vw)"
        :close-on-click-modal="false"
    >
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >País</label
                >
                <el-select
                    v-model="form.country_id"
                    size="small"
                    class="w-full"
                    filterable
                    @change="onCountry"
                >
                    <el-option
                        v-for="c in countries"
                        :key="c.id"
                        :label="c.Name"
                        :value="c.id"
                    />
                </el-select>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Departamento</label
                >
                <el-select
                    v-model="form.department_id"
                    size="small"
                    class="w-full"
                    filterable
                    :disabled="!form.country_id"
                    @change="form.city_id = null"
                >
                    <el-option
                        v-for="d in departamentosDelPais"
                        :key="d.id"
                        :label="d.nombre"
                        :value="d.id"
                    />
                </el-select>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Ciudad *</label
                >
                <el-select
                    v-model="form.city_id"
                    size="small"
                    class="w-full"
                    filterable
                    :disabled="!form.department_id"
                >
                    <el-option
                        v-for="c in ciudadesDelDepto"
                        :key="c.id"
                        :label="c.nombre"
                        :value="c.id"
                    />
                </el-select>
                <p v-if="errors.city_id" class="mt-1 text-xs text-red-500">
                    {{ errors.city_id }}
                </p>
            </div>

            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Tipo de documento *</label
                >
                <el-select
                    v-model="form.idtipo_documento"
                    size="small"
                    class="w-full"
                    filterable
                    :disabled="!form.country_id"
                >
                    <el-option
                        v-for="t in tiposDelPais"
                        :key="t.id"
                        :label="`${t.sigla} — ${t.nombre}`"
                        :value="t.id"
                    />
                </el-select>
                <p
                    v-if="errors.idtipo_documento"
                    class="mt-1 text-xs text-red-500"
                >
                    {{ errors.idtipo_documento }}
                </p>
            </div>
            <div class="md:col-span-2">
                <label class="text-muted-foreground text-xs font-semibold"
                    >Documento *</label
                >
                <el-input
                    v-model="form.documento"
                    size="small"
                    maxlength="20"
                />
                <p v-if="errors.documento" class="mt-1 text-xs text-red-500">
                    {{ errors.documento }}
                </p>
            </div>

            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Primer nombre / Razón social *</label
                >
                <el-input v-model="form.nombre" size="small" maxlength="100" />
                <p v-if="errors.nombre" class="mt-1 text-xs text-red-500">
                    {{ errors.nombre }}
                </p>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Segundo nombre</label
                >
                <el-input
                    v-model="form.nombre_segundo"
                    size="small"
                    maxlength="50"
                />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Apellido</label
                >
                <el-input v-model="form.apellido" size="small" maxlength="50" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Segundo apellido</label
                >
                <el-input
                    v-model="form.apellido_segundo"
                    size="small"
                    maxlength="50"
                />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Teléfono *</label
                >
                <el-input v-model="form.telefono" size="small" maxlength="15" />
                <p v-if="errors.telefono" class="mt-1 text-xs text-red-500">
                    {{ errors.telefono }}
                </p>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Correo *</label
                >
                <el-input v-model="form.email" size="small" maxlength="100" />
                <p v-if="errors.email" class="mt-1 text-xs text-red-500">
                    {{ errors.email }}
                </p>
            </div>

            <div class="md:col-span-3">
                <label class="text-muted-foreground text-xs font-semibold"
                    >Dirección *</label
                >
                <el-input
                    v-model="form.direccion"
                    type="textarea"
                    :rows="2"
                    maxlength="200"
                />
                <p v-if="errors.direccion" class="mt-1 text-xs text-red-500">
                    {{ errors.direccion }}
                </p>
            </div>
        </div>

        <template #footer>
            <el-button @click="store.cerrarModal()">Cancelar</el-button>
            <el-button
                type="primary"
                :loading="submitting"
                :disabled="!valido"
                @click="guardar"
            >
                {{ editing ? 'Actualizar' : 'Registrar' }}
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed, reactive, watch } from 'vue';
import { nuevoClienteVacio, usePrestamosStore } from '@/stores/prestamos';

const store = usePrestamosStore();
const { nuevoClienteOpen, clientesTables, tiposDocumento, creandoCliente } =
    storeToRefs(store);

const form = reactive(nuevoClienteVacio());

watch(nuevoClienteOpen, (v) => {
    if (v) Object.assign(form, nuevoClienteVacio());
});

const tiposDelPais = computed(() =>
    tiposDocumento.value.filter((t) => t.country_id === form.country_id),
);
const departamentosDelPais = computed(() =>
    clientesTables.value.DepartmentAll.filter(
        (d) => d.country_id === form.country_id,
    ),
);
const ciudadesDelDepto = computed(() =>
    clientesTables.value.CitiesAll.filter(
        (c) => c.department_id === form.department_id,
    ),
);

function onCountry() {
    form.department_id = null;
    form.city_id = null;
    form.idtipo_documento = null;
}

const valido = computed(
    () =>
        form.idtipo_documento &&
        form.documento.trim() &&
        form.nombre.trim() &&
        form.telefono.trim() &&
        form.direccion.trim() &&
        /.+@.+\..+/.test(form.email) &&
        form.city_id,
);

async function guardar() {
    if (!valido.value) {
        ElNotification.warning('Complete los campos obligatorios.');
        return;
    }
    try {
        const res = await store.crearClienteRapido({ ...form });
        if (res.success) {
            ElNotification.success('Cliente creado y seleccionado');
        } else {
            ElNotification.error('No se pudo crear el cliente');
        }
    } catch {
        ElNotification.error('Error al crear el cliente (revise los datos)');
    }
}
</script>

<template>
    <el-dialog
        v-model="nuevoClienteOpen"
        title="Nuevo cliente"
        width="min(680px, 94vw)"
        append-to-body
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
                    @change="onCountry"
                >
                    <el-option
                        v-for="c in clientesTables.CountryAll"
                        :key="c.id"
                        :label="c.Name"
                        :value="c.id"
                    />
                </el-select>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Tipo de documento *</label
                >
                <el-select
                    v-model="form.idtipo_documento"
                    size="small"
                    class="w-full"
                    :disabled="!form.country_id"
                >
                    <el-option
                        v-for="t in tiposDelPais"
                        :key="t.id"
                        :label="`${t.sigla} — ${t.nombre}`"
                        :value="t.id"
                    />
                </el-select>
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Documento *</label
                >
                <el-input v-model="form.documento" size="small" />
            </div>

            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Nombre *</label
                >
                <el-input v-model="form.nombre" size="small" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Segundo nombre</label
                >
                <el-input v-model="form.nombre_segundo" size="small" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Apellido</label
                >
                <el-input v-model="form.apellido" size="small" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Segundo apellido</label
                >
                <el-input v-model="form.apellido_segundo" size="small" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Teléfono *</label
                >
                <el-input v-model="form.telefono" size="small" />
            </div>
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Correo *</label
                >
                <el-input v-model="form.email" size="small" />
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
            </div>
            <div class="md:col-span-3">
                <label class="text-muted-foreground text-xs font-semibold"
                    >Dirección *</label
                >
                <el-input v-model="form.direccion" type="textarea" :rows="2" />
            </div>
        </div>

        <template #footer>
            <el-button @click="nuevoClienteOpen = false">Cancelar</el-button>
            <el-button
                type="primary"
                :loading="creandoCliente"
                :disabled="!valido"
                @click="guardar"
            >
                Crear cliente
            </el-button>
        </template>
    </el-dialog>
</template>

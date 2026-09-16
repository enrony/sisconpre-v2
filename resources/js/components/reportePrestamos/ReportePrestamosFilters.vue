<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useReportePrestamosStore } from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();
const {
    filtro,
    clientesLista,
    loadingClientes,
    countryAll,
    citiesAll,
    gruposTrabajoAll,
} = storeToRefs(store);

function aplicar() {
    void store.fetchList(1);
}

function limpiar() {
    store.resetFiltro();
    void store.fetchList(1);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <div class="md:col-span-3 lg:col-span-2">
            <label class="text-muted-foreground text-xs font-semibold"
                >Clientes</label
            >
            <el-select
                v-model="filtro.clientes"
                multiple
                filterable
                remote
                clearable
                size="small"
                class="w-full"
                placeholder="Buscar cliente por nombre"
                :remote-method="store.searchClientes"
                :loading="loadingClientes"
            >
                <el-option
                    v-for="c in clientesLista"
                    :key="c.id"
                    :label="`${c.nombre} ${c.apellido ?? ''}`"
                    :value="c.id"
                />
            </el-select>
        </div>

        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Fecha registro</label
            >
            <el-date-picker
                v-model="filtro.fecha_registro"
                type="daterange"
                size="small"
                class="!w-full"
                value-format="YYYY-MM-DD"
                start-placeholder="Desde"
                end-placeholder="Hasta"
            />
        </div>

        <div v-if="countryAll.length > 1">
            <label class="text-muted-foreground text-xs font-semibold"
                >País</label
            >
            <el-select
                v-model="filtro.pais"
                clearable
                size="small"
                class="w-full"
                placeholder="Todos"
            >
                <el-option
                    v-for="c in countryAll"
                    :key="c.id"
                    :label="c.Name"
                    :value="c.id"
                />
            </el-select>
        </div>

        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Ciudad</label
            >
            <el-select
                v-model="filtro.ciudad"
                filterable
                clearable
                size="small"
                class="w-full"
                placeholder="Todas"
            >
                <el-option
                    v-for="c in citiesAll"
                    :key="c.id"
                    :label="c.nombre"
                    :value="c.id"
                />
            </el-select>
        </div>

        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Grupo de trabajo</label
            >
            <el-select
                v-model="filtro.grupo"
                filterable
                clearable
                size="small"
                class="w-full"
                placeholder="Todos"
            >
                <el-option
                    v-for="g in gruposTrabajoAll"
                    :key="g.id"
                    :label="g.nombre"
                    :value="g.id"
                />
            </el-select>
        </div>

        <div class="flex items-end gap-2">
            <el-button type="primary" size="small" @click="aplicar"
                >Aplicar</el-button
            >
            <el-button size="small" @click="limpiar">Limpiar</el-button>
        </div>
    </div>
</template>

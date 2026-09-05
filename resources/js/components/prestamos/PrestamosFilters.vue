<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { usePrestamosStore } from '@/stores/prestamos';

const store = usePrestamosStore();
const { filtro, estados, clientesLista, loadingClientes } = storeToRefs(store);

function aplicar() {
    void store.fetchList(1);
}

function limpiar() {
    store.resetFiltro();
    void store.fetchList(1);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4 lg:grid-cols-6">
        <div class="md:col-span-4 lg:col-span-6">
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
        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Fecha inicio</label
            >
            <el-date-picker
                v-model="filtro.inicio"
                type="daterange"
                size="small"
                class="!w-full"
                value-format="YYYY-MM-DD"
                start-placeholder="Desde"
                end-placeholder="Hasta"
            />
        </div>
        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Fecha fin</label
            >
            <el-date-picker
                v-model="filtro.fin"
                type="daterange"
                size="small"
                class="!w-full"
                value-format="YYYY-MM-DD"
                start-placeholder="Desde"
                end-placeholder="Hasta"
            />
        </div>

        <div>
            <label class="text-muted-foreground text-xs font-semibold"
                >Estado</label
            >
            <el-select
                v-model="filtro.estado"
                multiple
                collapse-tags
                clearable
                size="small"
                class="w-full"
                placeholder="Todos"
            >
                <el-option
                    v-for="e in estados"
                    :key="e.id"
                    :label="e.description"
                    :value="e.id"
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

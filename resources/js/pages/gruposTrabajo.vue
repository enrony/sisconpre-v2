<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GrupoTrabajoFormModal from '@/components/maestros/GrupoTrabajoFormModal.vue';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'grupos_trabajo',
    title: 'Grupos de trabajo',
    singular: 'grupo de trabajo',
    labelProp: 'nombre',
    tablesUrl: '/grupos_trabajo/tables',
    columns: [
        { prop: 'nombre', label: 'Nombre' },
        { prop: 'code', label: 'Código', width: 120, align: 'center' },
        {
            prop: 'city_id',
            label: 'Ciudad',
            width: 200,
            lookupKey: 'CitiesAll',
            lookupLabel: 'nombre',
        },
        { prop: 'reference', label: 'Referencia' },
    ],
    // El modal de creación/edición es el de abajo (GrupoTrabajoFormModal),
    // por el campo "Código" con su propia UX — no lee esta lista.
    fields: [],
};
</script>

<template>
    <Head title="Grupos de trabajo" />
    <MaestroCrud :config="config">
        <template #modal="{ open, record, currentPage, tables, updateOpen }">
            <GrupoTrabajoFormModal
                :config="config"
                :open="open"
                :record="record"
                :current-page="currentPage"
                :tables="tables"
                @update:open="updateOpen"
            />
        </template>
    </MaestroCrud>
</template>

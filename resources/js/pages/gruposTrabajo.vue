<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
    fields: [
        { key: 'nombre', label: 'Nombre', span: 1 },
        { key: 'code', label: 'Código', span: 1 },
        {
            key: 'city_id',
            label: 'Ciudad',
            type: 'select',
            optionsKey: 'CitiesAll',
            optionLabel: 'nombre',
        },
        { key: 'reference', label: 'Referencia' },
    ],
};
</script>

<template>
    <Head title="Grupos de trabajo" />
    <MaestroCrud :config="config" />
</template>

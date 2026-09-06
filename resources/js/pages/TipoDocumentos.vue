<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'tipo_documentos',
    title: 'Tipos de documento',
    singular: 'tipo de documento',
    tablesUrl: '/tipo_documentos/tables',
    columns: [
        { prop: 'sigla', label: 'Sigla', width: 110, align: 'center' },
        { prop: 'nombre', label: 'Nombre' },
        {
            prop: 'country_id',
            label: 'País',
            width: 160,
            lookupKey: 'CountryAll',
            lookupLabel: 'Name',
        },
    ],
    fields: [
        { key: 'sigla', label: 'Sigla', span: 1 },
        { key: 'nombre', label: 'Nombre' },
        {
            key: 'country_id',
            label: 'País',
            type: 'select',
            optionsKey: 'CountryAll',
            optionLabel: 'Name',
        },
    ],
};
</script>

<template>
    <Head title="Tipos de documento" />
    <MaestroCrud :config="config" />
</template>

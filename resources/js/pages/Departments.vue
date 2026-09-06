<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'departamentos',
    title: 'Departamentos',
    singular: 'departamento',
    labelProp: 'nombre',
    tablesUrl: '/departamentos/tables',
    columns: [
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
    <Head title="Departamentos" />
    <MaestroCrud :config="config" />
</template>

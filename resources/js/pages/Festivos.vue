<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'festivos',
    title: 'Festivos',
    singular: 'festivo',
    labelProp: 'name',
    tablesUrl: '/festivos/tables',
    columns: [
        { prop: 'name', label: 'Nombre' },
        { prop: 'date', label: 'Fecha', width: 130, align: 'center' },
        { prop: 'year', label: 'Año', width: 90, align: 'center' },
        {
            prop: 'country_id',
            label: 'País',
            width: 160,
            lookupKey: 'CountryAll',
            lookupLabel: 'Name',
        },
    ],
    fields: [
        { key: 'name', label: 'Nombre' },
        { key: 'date', label: 'Fecha', type: 'date', span: 1 },
        {
            key: 'country_id',
            label: 'País',
            type: 'select',
            optionsKey: 'CountryAll',
            optionLabel: 'Name',
            span: 1,
        },
    ],
};
</script>

<template>
    <Head title="Festivos" />
    <MaestroCrud :config="config" />
</template>

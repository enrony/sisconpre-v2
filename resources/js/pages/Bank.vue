<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'banks',
    title: 'Bancos',
    singular: 'banco',
    tablesUrl: '/banks/tables',
    columns: [
        { prop: 'code', label: 'Código', width: 110, align: 'center' },
        { prop: 'description', label: 'Descripción' },
        {
            prop: 'country_id',
            label: 'País',
            width: 160,
            lookupKey: 'CountryAll',
            lookupLabel: 'Name',
        },
    ],
    fields: [
        { key: 'code', label: 'Código', span: 1 },
        { key: 'description', label: 'Descripción' },
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
    <Head title="Bancos" />
    <MaestroCrud :config="config" />
</template>

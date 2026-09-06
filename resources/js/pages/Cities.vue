<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'cities',
    title: 'Ciudades',
    singular: 'ciudad',
    labelProp: 'nombre',
    tablesUrl: '/cities/tables',
    columns: [
        { prop: 'nombre', label: 'Nombre' },
        {
            prop: 'department_id',
            label: 'Departamento',
            width: 200,
            lookupKey: 'DepartmentAll',
            lookupLabel: 'nombre',
        },
        {
            prop: 'country_id',
            label: 'País',
            width: 150,
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
            span: 1,
        },
        {
            key: 'department_id',
            label: 'Departamento',
            type: 'select',
            optionsKey: 'DepartmentAll',
            optionLabel: 'nombre',
            span: 1,
        },
    ],
};
</script>

<template>
    <Head title="Ciudades" />
    <MaestroCrud :config="config" />
</template>

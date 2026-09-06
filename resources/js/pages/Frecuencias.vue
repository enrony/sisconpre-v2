<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'frecuencias',
    title: 'Frecuencias',
    singular: 'frecuencia',
    tablesUrl: '/frecuencias/tables',
    columns: [
        { prop: 'nombre', label: 'Nombre' },
        { prop: 'cantidad', label: 'Cantidad', width: 110, align: 'center' },
        {
            prop: 'tipo_frecuencia_prestamo_id',
            label: 'Tipo de frecuencia',
            width: 180,
            lookupKey: 'TipoFrecuenciaPrestamoAll',
            lookupLabel: 'descripcion',
        },
    ],
    fields: [
        { key: 'nombre', label: 'Nombre' },
        { key: 'cantidad', label: 'Cantidad', type: 'number', span: 1 },
        {
            key: 'tipo_frecuencia_prestamo_id',
            label: 'Tipo de frecuencia',
            type: 'select',
            optionsKey: 'TipoFrecuenciaPrestamoAll',
            optionLabel: 'descripcion',
        },
    ],
};
</script>

<template>
    <Head title="Frecuencias" />
    <MaestroCrud :config="config" />
</template>

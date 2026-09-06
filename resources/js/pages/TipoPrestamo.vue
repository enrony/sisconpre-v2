<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'tipo_prestamo',
    title: 'Tipos de préstamo',
    singular: 'tipo de préstamo',
    labelProp: 'descripcion',
    tablesUrl: '/tipo_prestamo/tables',
    columns: [
        { prop: 'descripcion', label: 'Descripción' },
        { prop: 'cantidad', label: 'Cantidad', width: 110, align: 'center' },
        {
            prop: 'tipo_frecuencia_prestamo_id',
            label: 'Frecuencia',
            width: 180,
            lookupKey: 'TipoFrecuenciaPrestamoAll',
            lookupLabel: 'descripcion',
        },
    ],
    fields: [
        { key: 'descripcion', label: 'Descripción' },
        { key: 'cantidad', label: 'Cantidad', type: 'number', span: 1 },
        {
            key: 'tipo_frecuencia_prestamo_id',
            label: 'Frecuencia',
            type: 'select',
            optionsKey: 'TipoFrecuenciaPrestamoAll',
            optionLabel: 'descripcion',
            span: 1,
        },
    ],
};
</script>

<template>
    <Head title="Tipos de préstamo" />
    <MaestroCrud :config="config" />
</template>

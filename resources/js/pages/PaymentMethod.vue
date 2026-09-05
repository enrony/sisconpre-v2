<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'payment_methods',
    title: 'Métodos de pago',
    singular: 'método de pago',
    columns: [
        { prop: 'code', label: 'Código', width: 100, align: 'center' },
        { prop: 'description', label: 'Descripción' },
        {
            prop: 'bank',
            label: 'Banco',
            width: 90,
            align: 'center',
            boolean: true,
        },
        {
            prop: 'franchise',
            label: 'Franquicia',
            width: 100,
            align: 'center',
            boolean: true,
        },
        {
            prop: 'reference',
            label: 'Referencia',
            width: 100,
            align: 'center',
            boolean: true,
        },
        {
            prop: 'support',
            label: 'Soporte',
            width: 90,
            align: 'center',
            boolean: true,
        },
    ],
    fields: [
        { key: 'code', label: 'Código', span: 1 },
        { key: 'description', label: 'Descripción' },
        { key: 'bank', label: 'Requiere banco', type: 'switch', span: 1 },
        {
            key: 'franchise',
            label: 'Requiere franquicia',
            type: 'switch',
            span: 1,
        },
        {
            key: 'reference',
            label: 'Requiere referencia',
            type: 'switch',
            span: 1,
        },
        { key: 'box', label: 'Es caja', type: 'switch', span: 1 },
        { key: 'support', label: 'Requiere soporte', type: 'switch', span: 1 },
    ],
};
</script>

<template>
    <Head title="Métodos de pago" />
    <MaestroCrud :config="config" />
</template>

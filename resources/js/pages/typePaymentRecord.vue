<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'type_payment_record',
    title: 'Tipo registro de pagos',
    singular: 'tipo de registro',
    columns: [
        { prop: 'code', label: 'Código', width: 110, align: 'center' },
        { prop: 'description', label: 'Descripción' },
    ],
    fields: [
        { key: 'code', label: 'Código', span: 1 },
        { key: 'description', label: 'Descripción' },
    ],
};
</script>

<template>
    <Head title="Tipo registro de pagos" />
    <MaestroCrud :config="config" />
</template>

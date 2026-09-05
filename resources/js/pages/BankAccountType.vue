<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'bank_account_types',
    title: 'Tipos de cuentas bancarias',
    singular: 'tipo de cuenta',
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
    <Head title="Tipos de cuentas bancarias" />
    <MaestroCrud :config="config" />
</template>

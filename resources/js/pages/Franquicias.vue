<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MaestroCrud from '@/components/maestros/MaestroCrud.vue';
import type { MaestroConfig } from '@/components/maestros/types';

const config: MaestroConfig = {
    resource: 'franquicias',
    title: 'Franquicias',
    singular: 'franquicia',
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
    <Head title="Franquicias" />
    <MaestroCrud :config="config" />
</template>

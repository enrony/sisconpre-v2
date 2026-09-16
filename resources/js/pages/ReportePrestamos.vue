<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import Heading from '@/components/Heading.vue';
import ReportePrestamosFilters from '@/components/reportePrestamos/ReportePrestamosFilters.vue';
import ReportePrestamosTable from '@/components/reportePrestamos/ReportePrestamosTable.vue';
import { useReportePrestamosStore } from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();

onMounted(() => {
    void store.fetchList(1);
    void store.fetchTables();
});
</script>

<template>
    <Head title="Reporte de préstamos" />

    <div class="px-4 py-6">
        <div class="mb-4">
            <Heading
                title="Reporte de préstamos"
                description="Listado detallado, filtrable por cliente, fecha, país, ciudad y grupo de trabajo"
            />
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4">
                <ReportePrestamosFilters />
            </div>
            <ReportePrestamosTable />
        </div>
    </div>
</template>

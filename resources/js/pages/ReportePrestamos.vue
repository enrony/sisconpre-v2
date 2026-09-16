<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import Heading from '@/components/Heading.vue';
import ReportePrestamosFilters from '@/components/reportePrestamos/ReportePrestamosFilters.vue';
import ReportePrestamosTable from '@/components/reportePrestamos/ReportePrestamosTable.vue';
import ReporteToolbar from '@/components/reportes/ReporteToolbar.vue';
import { useReportePrestamosStore } from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();

onMounted(() => {
    void store.fetchList(1);
    void store.fetchPaises();
    void store.fetchEstados();
    void store.fetchGrupos();
});
</script>

<template>
    <Head title="Reporte de préstamos" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                title="Reporte de préstamos"
                description="Listado detallado, filtrable por cliente, fecha, estado, país, ciudad, grupo de trabajo y responsable"
            />
            <ReporteToolbar
                :url-excel="store.urlExportarExcel()"
                :url-pdf="store.urlExportarPdf()"
                :url-imprimir="store.urlExportarPdf(true)"
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

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import Heading from '@/components/Heading.vue';
import ReporteInformesPagoFilters from '@/components/reporteInformesPago/ReporteInformesPagoFilters.vue';
import ReporteInformesPagoTable from '@/components/reporteInformesPago/ReporteInformesPagoTable.vue';
import ReporteToolbar from '@/components/reportes/ReporteToolbar.vue';
import { useReporteInformesPagoStore } from '@/stores/reporteInformesPago';

const store = useReporteInformesPagoStore();

onMounted(() => {
    void store.fetchList(1);
    void store.fetchPaises();
    void store.fetchEstados();
    void store.fetchMetodos();
    void store.fetchGrupos();
    void store.fetchBancos();
});
</script>

<template>
    <Head title="Reporte de informes de pago" />

    <div class="px-4 py-6">
        <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                title="Reporte de informes de pago"
                description="Pagos recibidos, filtrable por cliente, fecha, estado, destino, método de pago, banco, país, ciudad, grupo de trabajo y responsable"
            />
            <ReporteToolbar
                class="shrink-0"
                :url-excel="store.urlExportarExcel()"
                :url-pdf="store.urlExportarPdf()"
                :url-imprimir="store.urlExportarPdf(true)"
            />
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4">
                <ReporteInformesPagoFilters />
            </div>
            <ReporteInformesPagoTable />
        </div>
    </div>
</template>

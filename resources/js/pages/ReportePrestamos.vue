<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ReportePrestamosFilters from '@/components/reportePrestamos/ReportePrestamosFilters.vue';
import ReportePrestamosResumen from '@/components/reportePrestamos/ReportePrestamosResumen.vue';
import ReportePrestamosTable from '@/components/reportePrestamos/ReportePrestamosTable.vue';
import ReporteToolbar from '@/components/reportes/ReporteToolbar.vue';
import { useReportePrestamosStore } from '@/stores/reportePrestamos';

const store = useReportePrestamosStore();
const vista = ref<'detallado' | 'consolidado'>('detallado');

onMounted(() => {
    void store.fetchList(1);
    void store.fetchResumen();
    void store.fetchPaises();
    void store.fetchEstados();
    void store.fetchGrupos();
});
</script>

<template>
    <Head title="Reporte de préstamos" />

    <div class="px-4 py-6">
        <div
            class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <Heading
                title="Reporte de préstamos"
                description="Filtrable por cliente, fecha, estado, país, ciudad, grupo de trabajo y responsable"
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
                <ReportePrestamosFilters />
            </div>

            <el-radio-group v-model="vista" size="small" class="mb-4">
                <el-radio-button value="detallado">Detallado</el-radio-button>
                <el-radio-button value="consolidado"
                    >Consolidado</el-radio-button
                >
            </el-radio-group>

            <ReportePrestamosResumen v-if="vista === 'consolidado'" />
            <ReportePrestamosTable v-else />
        </div>
    </div>
</template>

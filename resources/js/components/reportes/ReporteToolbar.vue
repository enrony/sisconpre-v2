<script setup lang="ts">
import { Download, FileSpreadsheet, Printer } from '@lucide/vue';

/**
 * Barra de Exportar Excel / Exportar PDF / Imprimir, reutilizable por
 * cualquier reporte: recibe las URLs ya armadas (con los filtros actuales
 * en el query string) y solo abre/navega — no sabe nada del reporte en sí.
 */
const props = defineProps<{
    urlExcel: string;
    urlPdf: string;
    urlImprimir: string;
}>();

function exportar(url: string) {
    window.open(url, '_blank');
}
</script>

<template>
    <div class="flex items-center gap-2">
        <el-tooltip content="Exportar a Excel">
            <el-button size="small" @click="exportar(props.urlExcel)">
                <FileSpreadsheet class="mr-1 size-4" /> Excel
            </el-button>
        </el-tooltip>
        <el-tooltip content="Exportar a PDF">
            <el-button size="small" @click="exportar(props.urlPdf)">
                <Download class="mr-1 size-4" /> PDF
            </el-button>
        </el-tooltip>
        <el-tooltip content="Imprimir">
            <el-button size="small" @click="exportar(props.urlImprimir)">
                <Printer class="mr-1 size-4" /> Imprimir
            </el-button>
        </el-tooltip>
    </div>
</template>

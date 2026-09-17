<script setup lang="ts">
import { Download, FileSpreadsheet, Printer } from '@lucide/vue';

/**
 * Barra de Exportar Excel / Exportar PDF / Imprimir, reutilizable por
 * cualquier reporte: recibe las URLs ya armadas (con los filtros actuales
 * en el query string) y solo abre/navega — no sabe nada del reporte en sí.
 *
 * Un solo grupo (no tres botones sueltos): mismo peso visual entre las tres
 * acciones a propósito — el azul de marca queda reservado para lo que
 * "pide atención" (cabeceras de diálogo), no decora exportaciones de rutina.
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
    <div
        role="group"
        aria-label="Exportar reporte"
        class="border-border bg-card inline-flex items-center gap-0.5 rounded-lg border p-1 shadow-xs"
    >
        <el-tooltip content="Exportar a Excel">
            <button
                type="button"
                class="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring/50 flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px]"
                aria-label="Exportar a Excel"
                @click="exportar(props.urlExcel)"
            >
                <FileSpreadsheet class="size-4 shrink-0" />
                <span class="hidden sm:inline">Excel</span>
            </button>
        </el-tooltip>

        <span class="bg-border h-5 w-px" aria-hidden="true" />

        <el-tooltip content="Exportar a PDF">
            <button
                type="button"
                class="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring/50 flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px]"
                aria-label="Exportar a PDF"
                @click="exportar(props.urlPdf)"
            >
                <Download class="size-4 shrink-0" />
                <span class="hidden sm:inline">PDF</span>
            </button>
        </el-tooltip>

        <span class="bg-border h-5 w-px" aria-hidden="true" />

        <el-tooltip content="Imprimir">
            <button
                type="button"
                class="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring/50 flex h-8 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px]"
                aria-label="Imprimir"
                @click="exportar(props.urlImprimir)"
            >
                <Printer class="size-4 shrink-0" />
                <span class="hidden sm:inline">Imprimir</span>
            </button>
        </el-tooltip>
    </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

/**
 * Tile de KPI reutilizable por cualquier reporte: una etiqueta + un valor ya
 * formateado. `tone="destructive"` es para cifras que deben leerse como
 * alerta (p. ej. "monto perdido") — nunca decorativo, siempre con ícono +
 * etiqueta (nunca solo color) para que no dependa de distinguir un tono.
 */
defineProps<{
    label: string;
    value: string;
    /** Línea chica debajo del valor (p. ej. "11 informes"). */
    sublabel?: string;
    tone?: 'default' | 'destructive';
    icon?: Component;
}>();
</script>

<template>
    <div class="bg-card rounded-lg border p-4">
        <p class="text-muted-foreground text-xs font-medium">{{ label }}</p>
        <p
            class="mt-1 flex items-center gap-1.5 text-2xl font-semibold"
            :class="
                tone === 'destructive' ? 'text-destructive' : 'text-foreground'
            "
        >
            <component :is="icon" v-if="icon" class="size-5 shrink-0" />
            {{ value }}
        </p>
        <p v-if="sublabel" class="text-muted-foreground mt-0.5 text-xs">
            {{ sublabel }}
        </p>
    </div>
</template>

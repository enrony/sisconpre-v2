<script setup lang="ts">
/**
 * Bandera del país de un grupo de trabajo. SVG y no emoji: en Windows los
 * emoji de bandera se muestran como letras ("CO"). Banderas simplificadas a
 * sus franjas (tamaño de ícono); un país sin dibujo cae al código en un chip.
 */
defineProps<{
    /** ISO 3166-1 alfa-2 (CO, VE, AR…). */
    codigo: string | null;
    titulo?: string | null;
}>();
</script>

<template>
    <span
        class="ring-border inline-flex h-3.5 w-5 shrink-0 overflow-hidden rounded-[3px] ring-1"
        role="img"
        :aria-label="titulo ?? codigo ?? 'País'"
        :title="titulo ?? undefined"
    >
        <svg
            v-if="codigo === 'CO'"
            viewBox="0 0 20 14"
            class="size-full"
            aria-hidden="true"
        >
            <rect width="20" height="7" fill="#FCD116" />
            <rect y="7" width="20" height="3.5" fill="#003893" />
            <rect y="10.5" width="20" height="3.5" fill="#CE1126" />
        </svg>
        <svg
            v-else-if="codigo === 'VE'"
            viewBox="0 0 20 14"
            class="size-full"
            aria-hidden="true"
        >
            <rect width="20" height="4.7" fill="#FFCC00" />
            <rect y="4.7" width="20" height="4.7" fill="#00247D" />
            <rect y="9.4" width="20" height="4.6" fill="#CF142B" />
            <path
                d="M6 8.6a4.4 4.4 0 0 1 8 0"
                fill="none"
                stroke="#FFFFFF"
                stroke-width="0.8"
                stroke-dasharray="0.6 0.55"
            />
        </svg>
        <svg
            v-else-if="codigo === 'AR'"
            viewBox="0 0 20 14"
            class="size-full"
            aria-hidden="true"
        >
            <rect width="20" height="4.7" fill="#74ACDF" />
            <rect y="4.7" width="20" height="4.7" fill="#FFFFFF" />
            <rect y="9.4" width="20" height="4.6" fill="#74ACDF" />
            <circle cx="10" cy="7" r="1.3" fill="#F6B40E" />
        </svg>
        <span
            v-else
            class="bg-muted text-muted-foreground flex size-full items-center justify-center text-[8px] leading-none font-semibold"
            >{{ codigo ?? '?' }}</span
        >
    </span>
</template>

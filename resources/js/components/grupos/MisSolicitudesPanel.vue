<script setup lang="ts">
import { Clock, Plus } from '@lucide/vue';
import { ref } from 'vue';
import UnirseGrupoDialog from '@/components/grupos/UnirseGrupoDialog.vue';
import type { MiSolicitud } from '@/types/grupos';

/**
 * Solicitudes del usuario que esperan la aprobación del dueño del grupo.
 *
 * Sin ningún grupo (recién registrado, o le rechazaron el ingreso) es lo
 * único que hay para ver: ocupa el lugar del tablero y explica qué falta.
 * Con grupos, es un aviso compacto arriba de sus tarjetas.
 */
defineProps<{ solicitudes: MiSolicitud[]; sinGrupo: boolean }>();

const unirseOpen = ref(false);
</script>

<template>
    <section
        v-if="sinGrupo"
        class="border-sidebar-border/70 dark:border-sidebar-border mx-auto w-full max-w-xl rounded-xl border p-6 sm:mt-6 sm:p-8"
        aria-labelledby="titulo-espera"
    >
        <template v-if="solicitudes.length">
            <div
                class="mb-4 flex size-10 items-center justify-center rounded-full"
                style="background: color-mix(in srgb, #0073c3 14%, transparent)"
            >
                <Clock
                    class="size-5"
                    style="color: #0073c3"
                    aria-hidden="true"
                />
            </div>
            <h2
                id="titulo-espera"
                class="text-foreground text-lg font-semibold"
            >
                Su ingreso está esperando aprobación
            </h2>
            <p class="text-muted-foreground mt-1.5 text-sm">
                El dueño del grupo ya recibió un correo con su solicitud. Cuando
                la apruebe le avisaremos por correo y aquí verá la información
                del grupo.
            </p>
            <ul class="divide-border border-border mt-5 divide-y border-y">
                <li
                    v-for="s in solicitudes"
                    :key="s.id"
                    class="flex items-center gap-3 py-3"
                >
                    <span class="min-w-0 flex-1">
                        <span
                            class="text-foreground block truncate text-sm font-medium"
                            >{{ s.grupo }}</span
                        >
                        <span class="text-muted-foreground block text-xs"
                            >Enviada el {{ s.fecha }}</span
                        >
                    </span>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                        style="
                            color: #0073c3;
                            background: color-mix(
                                in srgb,
                                #0073c3 12%,
                                transparent
                            );
                        "
                        >En espera</span
                    >
                </li>
            </ul>
        </template>
        <template v-else>
            <h2
                id="titulo-espera"
                class="text-foreground text-lg font-semibold"
            >
                Todavía no forma parte de ningún grupo
            </h2>
            <p class="text-muted-foreground mt-1.5 text-sm">
                Pida el código del grupo de trabajo a quien lo administra. El
                dueño del grupo tiene que aprobar su ingreso.
            </p>
        </template>
        <el-button class="mt-5" @click="unirseOpen = true">
            <Plus class="mr-1.5 size-3.5" aria-hidden="true" />
            {{
                solicitudes.length
                    ? 'Unirme a otro grupo con un código'
                    : 'Unirme a un grupo con un código'
            }}
        </el-button>
        <UnirseGrupoDialog v-model:open="unirseOpen" />
    </section>

    <section
        v-else-if="solicitudes.length"
        class="border-sidebar-border/70 dark:border-sidebar-border flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border px-4 py-3"
        aria-label="Solicitudes en espera"
    >
        <Clock
            class="size-4 shrink-0"
            style="color: #0073c3"
            aria-hidden="true"
        />
        <p class="text-foreground text-sm">
            {{
                solicitudes.length === 1
                    ? 'Su solicitud para unirse a'
                    : 'Sus solicitudes para unirse a'
            }}
            <b class="font-medium">{{
                solicitudes.map((s) => s.grupo).join(', ')
            }}</b>
            {{
                solicitudes.length === 1
                    ? 'está esperando la aprobación del dueño del grupo.'
                    : 'están esperando la aprobación de sus dueños.'
            }}
        </p>
    </section>
</template>

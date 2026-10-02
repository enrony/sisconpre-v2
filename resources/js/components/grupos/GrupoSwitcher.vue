<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Globe } from '@lucide/vue';
import { computed, ref } from 'vue';
import BanderaPais from '@/components/grupos/BanderaPais.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import http from '@/lib/http';
import type { GrupoOpcion } from '@/types/grupos';

/**
 * Grupo de trabajo activo (encabezado). Todo lo que el usuario ve se filtra
 * por este grupo y su país; el super-usuario puede elegir "Todos los grupos".
 * Al cambiar se recarga la página entera: los listados viven en stores que
 * no se enteran solos del cambio.
 */
const page = usePage();
const grupos = computed(() => page.props.gruposTrabajo);
const opciones = computed(() => grupos.value?.opciones ?? []);
const activo = computed<GrupoOpcion | null>(() =>
    grupos.value?.todos ? null : (opciones.value.find((o) => o.activo) ?? null),
);
const verTodos = computed(() => Boolean(grupos.value?.todos));
const hayAlternativas = computed(
    () => opciones.value.length > 1 || Boolean(grupos.value?.puedeVerTodos),
);

const cambiando = ref(false);

async function elegir(gtuId: number | null) {
    if (cambiando.value) return;
    if (gtuId === null ? verTodos.value : activo.value?.gtu_id === gtuId) {
        return;
    }
    cambiando.value = true;
    try {
        await http.put('/grupo-activo', { gtu_id: gtuId });
        window.location.reload();
    } catch {
        cambiando.value = false;
        ElNotification.error('No se pudo cambiar de grupo de trabajo');
    }
}
</script>

<template>
    <DropdownMenu v-if="grupos && (opciones.length || grupos.puedeVerTodos)">
        <DropdownMenuTrigger as-child :disabled="!hayAlternativas">
            <button
                type="button"
                class="border-border hover:bg-accent focus-visible:ring-ring/50 flex h-9 max-w-[16rem] items-center gap-2 rounded-md border px-2.5 text-sm transition-colors outline-none focus-visible:ring-[3px] disabled:cursor-default disabled:hover:bg-transparent"
                :aria-label="`Grupo de trabajo: ${activo?.nombre ?? 'Todos los grupos'}`"
            >
                <Globe
                    v-if="verTodos"
                    class="text-muted-foreground size-4 shrink-0"
                    aria-hidden="true"
                />
                <BanderaPais
                    v-else
                    :codigo="activo?.pais_codigo ?? null"
                    :titulo="activo?.pais"
                />
                <span class="truncate font-medium">{{
                    activo?.nombre ?? 'Todos los grupos'
                }}</span>
                <span
                    v-if="activo?.ciudad"
                    class="text-muted-foreground hidden truncate sm:inline"
                    >· {{ activo.ciudad }}</span
                >
                <ChevronsUpDown
                    v-if="hayAlternativas"
                    class="text-muted-foreground size-3.5 shrink-0"
                    aria-hidden="true"
                />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel
                class="text-muted-foreground text-xs font-normal"
            >
                Grupo de trabajo
            </DropdownMenuLabel>
            <DropdownMenuItem
                v-if="grupos.puedeVerTodos"
                class="gap-2"
                @select="elegir(null)"
            >
                <Globe
                    class="text-muted-foreground size-4"
                    aria-hidden="true"
                />
                <span class="flex-1">Todos los grupos</span>
                <Check v-if="verTodos" class="size-4" aria-hidden="true" />
            </DropdownMenuItem>
            <DropdownMenuSeparator
                v-if="grupos.puedeVerTodos && opciones.length"
            />
            <DropdownMenuItem
                v-for="o in opciones"
                :key="o.gtu_id"
                class="gap-2"
                @select="elegir(o.gtu_id)"
            >
                <BanderaPais :codigo="o.pais_codigo" :titulo="o.pais" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate">{{ o.nombre }}</span>
                    <span
                        v-if="o.ciudad || o.pais"
                        class="text-muted-foreground block truncate text-xs"
                        >{{
                            [o.ciudad, o.pais].filter(Boolean).join(', ')
                        }}</span
                    >
                </span>
                <Check
                    v-if="!verTodos && o.activo"
                    class="size-4"
                    aria-hidden="true"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowRightLeft, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import BanderaPais from '@/components/grupos/BanderaPais.vue';
import http from '@/lib/http';
import { formatNumber } from '@/lib/format';
import type { TarjetaGrupo } from '@/types/grupos';

/**
 * "Mis grupos": una tarjeta por cada grupo de trabajo del usuario con su
 * resumen, para ver cómo está cada uno sin tener que cambiar de grupo. La
 * del grupo activo va resaltada; las demás permiten pasar a ese grupo.
 */
const props = defineProps<{ grupos: TarjetaGrupo[] }>();

const page = usePage();
/** Super-usuario mirando "Todos los grupos": ninguna tarjeta es la activa. */
const verTodos = computed(() => Boolean(page.props.gruposTrabajo?.todos));
const esActiva = (g: TarjetaGrupo) => !verTodos.value && g.activo;

const titulo = computed(() =>
    props.grupos.length === 1 ? 'Mi grupo' : 'Mis grupos',
);

const cambiando = ref<number | null>(null);

async function cambiar(g: TarjetaGrupo) {
    if (cambiando.value !== null) return;
    cambiando.value = g.gtu_id;
    try {
        await http.put('/grupo-activo', { gtu_id: g.gtu_id });
        window.location.reload();
    } catch {
        cambiando.value = null;
        ElNotification.error('No se pudo cambiar de grupo de trabajo');
    }
}

/** "2026-10-03" -> "03/10/2026". */
function fecha(iso: string): string {
    const [a, m, d] = iso.split('-');
    return `${d}/${m}/${a}`;
}
</script>

<template>
    <section v-if="grupos.length" aria-labelledby="titulo-mis-grupos">
        <div class="mb-3 flex items-center gap-2">
            <Users class="text-muted-foreground size-4" aria-hidden="true" />
            <h2
                id="titulo-mis-grupos"
                class="text-foreground text-sm font-medium"
            >
                {{ titulo }}
            </h2>
            <span class="text-muted-foreground text-xs tabular-nums"
                >· {{ grupos.length }}</span
            >
        </div>

        <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="g in grupos"
                :key="g.gtu_id"
                class="flex flex-col rounded-xl border p-4 transition-colors"
                :class="
                    esActiva(g)
                        ? 'border-[#0073c3] shadow-[0_0_0_1px_#0073c3]'
                        : 'border-sidebar-border/70 dark:border-sidebar-border'
                "
            >
                <!-- Encabezado: país, grupo y estado -->
                <div class="flex items-start gap-3">
                    <BanderaPais
                        :codigo="g.pais_codigo"
                        :titulo="g.pais"
                        class="mt-1"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-foreground truncate text-base font-semibold"
                        >
                            {{ g.nombre }}
                        </p>
                        <p
                            v-if="g.ciudad || g.pais"
                            class="text-muted-foreground truncate text-xs"
                        >
                            {{ [g.ciudad, g.pais].filter(Boolean).join(', ') }}
                        </p>
                    </div>
                    <span
                        v-if="esActiva(g)"
                        class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium text-white"
                        style="background: #0073c3"
                        >Grupo actual</span
                    >
                </div>

                <!-- Personal: cartera del grupo -->
                <template v-if="g.tipo === 'personal'">
                    <div class="mt-4">
                        <p class="text-muted-foreground text-xs">
                            Cartera activa
                        </p>
                        <p
                            class="text-foreground text-2xl font-semibold tabular-nums"
                        >
                            {{ formatNumber(g.datos.cartera_activa.monto) }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            {{ formatNumber(g.datos.cartera_activa.cantidad) }}
                            préstamos en curso
                        </p>
                    </div>
                    <dl
                        class="border-border mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t pt-3"
                    >
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                A cobrar hoy
                            </dt>
                            <dd
                                class="text-foreground text-sm font-medium tabular-nums"
                            >
                                {{ formatNumber(g.datos.cobrar_hoy.monto) }}
                                <span
                                    v-if="g.datos.cobrar_hoy.cantidad"
                                    class="text-muted-foreground text-xs font-normal"
                                    >·
                                    {{ g.datos.cobrar_hoy.cantidad }}
                                    cuotas</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Vencido
                            </dt>
                            <dd
                                class="text-sm font-medium tabular-nums"
                                :class="
                                    g.datos.vencido.cantidad
                                        ? 'text-destructive'
                                        : 'text-foreground'
                                "
                            >
                                {{ formatNumber(g.datos.vencido.monto) }}
                                <span
                                    v-if="g.datos.vencido.cantidad"
                                    class="text-muted-foreground text-xs font-normal"
                                    >·
                                    {{ g.datos.vencido.cantidad }} cuotas</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Pagos por revisar
                            </dt>
                            <dd
                                class="text-sm font-medium tabular-nums"
                                :style="
                                    g.datos.pagos_por_revisar
                                        ? 'color: #0073c3'
                                        : ''
                                "
                            >
                                {{ g.datos.pagos_por_revisar }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Préstamos por aprobar
                            </dt>
                            <dd
                                class="text-sm font-medium tabular-nums"
                                :style="
                                    g.datos.prestamos_por_aprobar
                                        ? 'color: #0073c3'
                                        : ''
                                "
                            >
                                {{ g.datos.prestamos_por_aprobar }}
                            </dd>
                        </div>
                    </dl>
                </template>

                <!-- Cliente: lo suyo en ese grupo -->
                <template v-else>
                    <div class="mt-4">
                        <p class="text-muted-foreground text-xs">
                            Saldo pendiente
                        </p>
                        <p
                            class="text-foreground text-2xl font-semibold tabular-nums"
                        >
                            {{ formatNumber(g.datos.saldo_pendiente) }}
                        </p>
                        <p class="text-muted-foreground text-xs">
                            {{ g.datos.prestamos_activos }}
                            {{
                                g.datos.prestamos_activos === 1
                                    ? 'préstamo activo'
                                    : 'préstamos activos'
                            }}
                        </p>
                    </div>
                    <dl
                        class="border-border mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t pt-3"
                    >
                        <div class="col-span-2">
                            <dt class="text-muted-foreground text-xs">
                                Próxima cuota
                            </dt>
                            <dd
                                class="text-foreground text-sm font-medium tabular-nums"
                            >
                                <template v-if="g.datos.proxima_cuota">
                                    {{
                                        formatNumber(
                                            g.datos.proxima_cuota.monto,
                                        )
                                    }}
                                    <span
                                        class="text-muted-foreground text-xs font-normal"
                                        >· vence el
                                        {{
                                            fecha(g.datos.proxima_cuota.fecha)
                                        }}</span
                                    >
                                </template>
                                <span
                                    v-else
                                    class="text-muted-foreground font-normal"
                                    >Sin cuotas por vencer</span
                                >
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Cuotas vencidas
                            </dt>
                            <dd
                                class="text-sm font-medium tabular-nums"
                                :class="
                                    g.datos.cuotas_vencidas
                                        ? 'text-destructive'
                                        : 'text-foreground'
                                "
                            >
                                {{ g.datos.cuotas_vencidas }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground text-xs">
                                Pagos en revisión
                            </dt>
                            <dd
                                class="text-sm font-medium tabular-nums"
                                :style="
                                    g.datos.pagos_en_revision
                                        ? 'color: #0073c3'
                                        : ''
                                "
                            >
                                {{ g.datos.pagos_en_revision }}
                            </dd>
                        </div>
                    </dl>
                </template>

                <div v-if="!esActiva(g)" class="mt-auto pt-4">
                    <el-button
                        class="w-full"
                        :loading="cambiando === g.gtu_id"
                        :disabled="cambiando !== null && cambiando !== g.gtu_id"
                        @click="cambiar(g)"
                    >
                        <ArrowRightLeft
                            class="mr-1.5 size-3.5"
                            aria-hidden="true"
                        />
                        Cambiar a este grupo
                    </el-button>
                </div>
            </li>
        </ul>
    </section>
</template>

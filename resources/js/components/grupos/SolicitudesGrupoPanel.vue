<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { UserPlus } from '@lucide/vue';
import { ref } from 'vue';
import http from '@/lib/http';
import type { SolicitudPorDecidir } from '@/types/grupos';

/**
 * Solicitudes para unirse a los grupos de los que el usuario es dueño (o
 * todas, si es super-usuario). Se ve solo si hay alguna pendiente.
 */
defineProps<{ solicitudes: SolicitudPorDecidir[] }>();

const procesando = ref<number | null>(null);

async function decidir(s: SolicitudPorDecidir, aprobar: boolean) {
    if (!aprobar) {
        try {
            await ElMessageBox.confirm(
                `${s.nombre ?? 'Este usuario'} no podrá ver ni operar el grupo ${s.grupo ?? ''}. Le avisaremos por correo.`,
                'Rechazar solicitud',
                {
                    type: 'warning',
                    confirmButtonText: 'Rechazar',
                    cancelButtonText: 'Cancelar',
                },
            );
        } catch {
            return;
        }
    }

    procesando.value = s.id;
    try {
        const { data } = await http.put(
            `/grupos/solicitudes/${s.id}/${aprobar ? 'aprobar' : 'rechazar'}`,
        );
        ElNotification.success(data.message);
        router.reload({ only: ['solicitudesPorDecidir'] });
    } catch (e: unknown) {
        const respuesta = (e as { response?: { data?: { message?: string } } })
            .response;
        ElNotification.error(
            respuesta?.data?.message ?? 'No se pudo resolver la solicitud',
        );
    } finally {
        procesando.value = null;
    }
}
</script>

<template>
    <section
        v-if="solicitudes.length"
        class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
        aria-labelledby="titulo-solicitudes"
    >
        <div class="mb-3 flex items-center gap-2">
            <UserPlus
                class="size-4"
                style="color: #0073c3"
                aria-hidden="true"
            />
            <h2
                id="titulo-solicitudes"
                class="text-foreground text-sm font-medium"
            >
                Solicitudes para unirse a sus grupos
            </h2>
            <span
                class="rounded-full px-2 py-0.5 text-xs font-medium text-white tabular-nums"
                style="background: #0073c3"
                >{{ solicitudes.length }}</span
            >
        </div>

        <ul class="divide-border divide-y">
            <li
                v-for="s in solicitudes"
                :key="s.id"
                class="flex flex-wrap items-center gap-x-4 gap-y-2 py-2.5"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-foreground truncate text-sm font-medium">
                        {{ s.nombre }}
                        <span class="text-muted-foreground font-normal"
                            >· {{ s.email }}</span
                        >
                    </p>
                    <p class="text-muted-foreground text-xs">
                        <span
                            v-if="s.registro"
                            class="text-foreground bg-muted mr-1.5 rounded px-1.5 py-px font-medium"
                            >Nuevo usuario</span
                        >
                        {{
                            s.registro
                                ? 'Se registró con el código de'
                                : 'Quiere unirse a'
                        }}
                        <b class="font-medium">{{ s.grupo }}</b> · {{ s.fecha }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <el-button
                        size="small"
                        type="success"
                        :loading="procesando === s.id"
                        @click="decidir(s, true)"
                        >Aprobar</el-button
                    >
                    <el-button
                        size="small"
                        plain
                        :disabled="procesando === s.id"
                        @click="decidir(s, false)"
                        >Rechazar</el-button
                    >
                </div>
            </li>
        </ul>
    </section>
</template>

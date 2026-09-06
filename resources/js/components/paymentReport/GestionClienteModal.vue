<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import SupportUpload from '@/components/paymentReport/SupportUpload.vue';
import { formatNumber } from '@/lib/format';
import {
    type GestionReporteRow,
    usePaymentReportStore,
} from '@/stores/paymentReport';

const store = usePaymentReportStore();
const {
    gestionOpen,
    gestionLoading,
    gestionCliente,
    gestionReportes,
    estados,
} = storeToRefs(store);

const titulo = computed(() => {
    const c = gestionCliente.value;
    const nom = c
        ? `${c.documento ?? ''} ${c.nombre ?? ''} ${c.apellido ?? ''}`.trim()
        : '';
    return nom
        ? `Gestión de informes — ${nom}`
        : 'Gestión de informes del cliente';
});

function requiereMotivo(row: GestionReporteRow): boolean {
    return Boolean(
        estados.value.find((e) => e.id === row.estatusSelected)?.motivo,
    );
}

function requiereSoporte(row: GestionReporteRow): boolean {
    return Boolean(
        estados.value.find((e) => e.id === row.estatusSelected)?.soporte,
    );
}

function tagType(
    row: GestionReporteRow,
): 'primary' | 'success' | 'info' | 'warning' | 'danger' {
    const c = row.status_description?.color ?? '';
    if (c.includes('green')) return 'success';
    if (c.includes('red')) return 'danger';
    if (c.includes('yellow')) return 'warning';
    return 'info';
}

async function procesar(row: GestionReporteRow) {
    if (requiereMotivo(row as GestionReporteRow) && !row.motivoEdit.trim()) {
        ElNotification.warning('Indique el motivo del cambio de estado.');
        return;
    }
    if (
        requiereSoporte(row as GestionReporteRow) &&
        row.soportes.length === 0
    ) {
        ElNotification.warning('Adjunte el soporte del cambio de estado.');
        return;
    }
    try {
        const res = await store.procesarGestion(row as GestionReporteRow);
        if (res?.success) {
            ElNotification.success(res.message ?? 'Estado actualizado');
        } else if (res) {
            ElNotification.error(res.message ?? 'No se pudo cambiar el estado');
        }
    } catch {
        ElNotification.error('Error al procesar el informe');
    }
}
</script>

<template>
    <el-dialog
        v-model="gestionOpen"
        :title="titulo"
        width="min(960px, 96vw)"
        top="5vh"
    >
        <div v-loading="gestionLoading">
            <p class="text-muted-foreground mb-2 text-sm">
                {{ gestionReportes.length }} informe(s) pendiente(s)
            </p>

            <el-empty
                v-if="!gestionLoading && gestionReportes.length === 0"
                description="El cliente no tiene informes pendientes"
            />

            <el-table
                v-else
                :data="gestionReportes"
                stripe
                border
                size="small"
                max-height="460"
                style="width: 100%"
            >
                <el-table-column
                    prop="id"
                    label="#"
                    width="60"
                    align="center"
                />
                <el-table-column
                    prop="created"
                    label="Registrado"
                    width="150"
                />
                <el-table-column
                    prop="destination_text"
                    label="Destino"
                    width="120"
                />
                <el-table-column
                    prop="number_cuotas"
                    label="Cuotas"
                    width="80"
                    align="center"
                />
                <el-table-column label="A favor" width="110" align="right">
                    <template #default="{ row }">
                        {{ formatNumber(row.importe) }}
                    </template>
                </el-table-column>
                <el-table-column label="Importe" width="120" align="right">
                    <template #default="{ row }">
                        <span class="font-semibold text-green-600">
                            {{ formatNumber(row.value_amount) }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column
                    label="Estado actual"
                    width="130"
                    align="center"
                >
                    <template #default="{ row }">
                        <el-tag
                            :type="tagType(row as GestionReporteRow)"
                            effect="dark"
                            size="small"
                        >
                            {{ row.status_description?.desc ?? '—' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="Nuevo estado" min-width="180">
                    <template #default="{ row }">
                        <el-select
                            v-model="row.estatusSelected"
                            size="small"
                            clearable
                            class="w-full"
                            :disabled="row.status_description?.finish_estatus"
                            placeholder="Seleccione"
                        >
                            <el-option
                                v-for="e in store.estadosParaFila(
                                    row as GestionReporteRow,
                                )"
                                :key="e.id"
                                :label="e.action_description"
                                :value="e.id"
                            />
                        </el-select>
                    </template>
                </el-table-column>
                <el-table-column label="Motivo / soporte" min-width="200">
                    <template #default="{ row }">
                        <div class="space-y-1">
                            <el-input
                                v-if="requiereMotivo(row as GestionReporteRow)"
                                v-model="row.motivoEdit"
                                size="small"
                                placeholder="Indique el motivo"
                            />
                            <SupportUpload
                                v-if="requiereSoporte(row as GestionReporteRow)"
                                v-model="row.soportes"
                                multiple
                                label="Soporte"
                            />
                            <span
                                v-if="
                                    !requiereMotivo(row as GestionReporteRow) &&
                                    !requiereSoporte(row as GestionReporteRow)
                                "
                                class="text-muted-foreground text-xs"
                                >—</span
                            >
                        </div>
                    </template>
                </el-table-column>
                <el-table-column
                    label=""
                    width="110"
                    align="center"
                    fixed="right"
                >
                    <template #default="{ row }">
                        <el-button
                            type="primary"
                            size="small"
                            :loading="row.procesando"
                            :disabled="!row.estatusSelected"
                            @click="procesar(row as GestionReporteRow)"
                        >
                            Procesar
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <template #footer>
            <el-button @click="store.cerrarGestion()">Cerrar</el-button>
        </template>
    </el-dialog>
</template>

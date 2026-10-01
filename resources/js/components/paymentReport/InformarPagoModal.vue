<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import SupportUpload from '@/components/paymentReport/SupportUpload.vue';
import { formatNumber } from '@/lib/format';
import {
    type CuotaPendiente,
    usePaymentReportStore,
} from '@/stores/paymentReport';

const store = usePaymentReportStore();
const {
    informarOpen,
    informar,
    clientesAll,
    paymentMethods,
    banks,
    franquicias,
    prestamosActivos,
    loadingActivos,
    informarSubmitting,
    saldoCliente,
} = storeToRefs(store);

const diferencia = computed(() => store.totalPagos - store.totalCuotas);

function metodo(id: number | null) {
    return paymentMethods.value.find((m) => m.id === id);
}

const puedeRegistrar = computed(() => {
    if (!informar.value.clienteId) return false;

    if (store.conSaldo) {
        return (
            informar.value.cuotas.length > 0 &&
            store.totalCuotas <= store.saldoDisponible
        );
    }

    if (store.totalPagos <= 0) return false;
    if (informar.value.tipoPago === 1) {
        // Con dinero, el pago no puede ser menor que las cuotas elegidas.
        if (informar.value.cuotas.length === 0 || diferencia.value < 0) {
            return false;
        }
    }

    // Las filas de método vacías se ignoran al enviar.
    return informar.value.dataPayments.every(
        (p) =>
            (!p.payment_method_id && !Number(p.valor_importe)) ||
            (p.payment_method_id && Number(p.valor_importe) > 0),
    );
});

function tooltipCuota(d: CuotaPendiente): string {
    if (d.pendientesPago2) {
        return `Pendiente de confirmación — informe #${d.payment_report_id_pendiente}`;
    }
    return 'Supera el saldo a favor que queda disponible';
}

async function registrar() {
    if (store.conSaldo) {
        try {
            await ElMessageBox.confirm(
                `Se pagarán ${informar.value.cuotas.length} cuota(s) por ${formatNumber(store.totalCuotas)} con el saldo a favor del cliente. El pago queda aprobado en el acto y no se puede deshacer.`,
                'Pagar con saldo a favor',
                {
                    confirmButtonText: 'Pagar con saldo a favor',
                    cancelButtonText: 'Cancelar',
                    type: 'warning',
                },
            );
        } catch {
            return;
        }
    }

    try {
        const res = await store.submitInformar();
        if (res.success) {
            ElNotification.success(res.message ?? 'Pago registrado');
            store.cerrarInformar();
            await store.fetchList(1);
        } else {
            ElNotification.error(res.message ?? 'No se pudo registrar el pago');
        }
    } catch {
        ElNotification.error('Error al registrar el pago');
    }
}
</script>

<template>
    <el-dialog
        v-model="informarOpen"
        title="Informar un pago"
        width="min(900px, 94vw)"
        :close-on-click-modal="false"
    >
        <div class="space-y-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="text-muted-foreground mb-1.5 block text-xs font-semibold"
                        >Cliente</label
                    >
                    <el-select
                        v-model="informar.clienteId"
                        filterable
                        clearable
                        size="small"
                        class="w-full"
                        placeholder="Seleccione el cliente"
                        @change="store.cargarActivos()"
                    >
                        <el-option
                            v-for="c in clientesAll"
                            :key="c.id"
                            :label="`${c.documento}: ${c.nombre} ${c.apellido ?? ''}`"
                            :value="c.id"
                        />
                    </el-select>
                </div>
                <div>
                    <label
                        class="text-muted-foreground mb-1.5 block text-xs font-semibold"
                        >Destino del pago</label
                    >
                    <el-radio-group v-model="informar.tipoPago" size="small">
                        <el-radio :value="1">Pago de cuotas</el-radio>
                        <el-radio :value="2">Abonar a saldo a favor</el-radio>
                    </el-radio-group>
                </div>
            </div>

            <!-- Modalidad: con dinero o con el saldo a favor del cliente -->
            <div
                v-if="
                    informar.tipoPago === 1 &&
                    informar.clienteId &&
                    store.saldoDisponible > 0
                "
                class="border-border bg-muted/40 rounded-md border px-3 py-2.5"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-sm font-semibold">¿Cómo paga?</span>
                    <el-radio-group
                        :model-value="informar.pagoConSaldo"
                        size="small"
                        @change="store.setPagoConSaldo(Boolean($event))"
                    >
                        <el-radio-button :value="false"
                            >Con dinero</el-radio-button
                        >
                        <el-radio-button :value="true"
                            >Con saldo a favor</el-radio-button
                        >
                    </el-radio-group>
                </div>
                <p class="text-muted-foreground mt-1 text-xs tabular-nums">
                    Saldo a favor disponible:
                    <b class="text-foreground">{{
                        formatNumber(store.saldoDisponible)
                    }}</b>
                    <template v-if="(saldoCliente?.reservado ?? 0) > 0">
                        · {{ formatNumber(saldoCliente?.reservado) }} reservado
                        en pagos por aprobar
                    </template>
                    <template v-if="store.conSaldo">
                        · elija cuotas que no superen ese monto; el pago se
                        aprueba en el acto.
                    </template>
                </p>
            </div>

            <!-- Cuotas pendientes -->
            <div
                v-if="informar.tipoPago === 1 && informar.clienteId"
                v-loading="loadingActivos"
            >
                <div class="mb-2 text-sm font-semibold">
                    Cuotas pendientes por préstamo
                </div>
                <el-empty
                    v-if="!prestamosActivos.length"
                    description="Sin préstamos activos con cuotas pendientes"
                    :image-size="60"
                />
                <el-collapse v-else>
                    <el-collapse-item
                        v-for="p in prestamosActivos"
                        :key="p.id"
                        :title="`Préstamo #${p.id} — monto ${formatNumber(p.monto_prestamo)}`"
                        :name="p.id"
                    >
                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                            <el-tooltip
                                v-for="d in p.prestamos_dias.filter(
                                    (x) => x.apply && !x.pagado,
                                )"
                                :key="d.id"
                                :disabled="
                                    !d.pendientesPago2 &&
                                    !store.cuotaSuperaSaldo(d)
                                "
                                :content="tooltipCuota(d)"
                            >
                                <label
                                    class="flex items-center gap-2 rounded border p-1 text-xs"
                                    :class="{
                                        'opacity-50':
                                            d.pendientesPago2 ||
                                            store.cuotaSuperaSaldo(d),
                                    }"
                                >
                                    <el-checkbox
                                        :model-value="
                                            store.cuotaSeleccionada(d.id)
                                        "
                                        :disabled="
                                            d.pendientesPago2 ||
                                            store.cuotaSuperaSaldo(d)
                                        "
                                        @change="store.toggleCuota(d)"
                                    />
                                    <span
                                        >{{ d.date }} ·
                                        {{ formatNumber(d.cuota) }}</span
                                    >
                                </label>
                            </el-tooltip>
                        </div>
                    </el-collapse-item>
                </el-collapse>
            </div>

            <!-- Métodos de pago (no aplican al pago con saldo a favor) -->
            <div v-if="!store.conSaldo">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-semibold">Métodos de pago</span>
                    <el-button size="small" @click="store.addPago()"
                        >Agregar método</el-button
                    >
                </div>
                <div
                    v-for="(pago, i) in informar.dataPayments"
                    :key="i"
                    class="mb-2 grid grid-cols-2 items-end gap-2 rounded border p-2 md:grid-cols-5"
                >
                    <div>
                        <label class="text-muted-foreground text-xs"
                            >Método</label
                        >
                        <el-select
                            v-model="pago.payment_method_id"
                            size="small"
                            class="w-full"
                        >
                            <el-option
                                v-for="m in paymentMethods"
                                :key="m.id"
                                :label="m.description"
                                :value="m.id"
                            />
                        </el-select>
                    </div>
                    <div>
                        <label class="text-muted-foreground text-xs"
                            >Importe</label
                        >
                        <el-input-number
                            v-model="pago.valor_importe"
                            size="small"
                            class="!w-full"
                            :min="0"
                            controls-position="right"
                        />
                    </div>
                    <div v-if="metodo(pago.payment_method_id)?.bank">
                        <label class="text-muted-foreground text-xs"
                            >Banco</label
                        >
                        <el-select
                            v-model="pago.bank_id"
                            size="small"
                            class="w-full"
                            clearable
                        >
                            <el-option
                                v-for="b in banks"
                                :key="b.id"
                                :label="b.description"
                                :value="b.id"
                            />
                        </el-select>
                    </div>
                    <div v-if="metodo(pago.payment_method_id)?.franchise">
                        <label class="text-muted-foreground text-xs"
                            >Franquicia</label
                        >
                        <el-select
                            v-model="pago.franquicia_id"
                            size="small"
                            class="w-full"
                            clearable
                        >
                            <el-option
                                v-for="f in franquicias"
                                :key="f.id"
                                :label="f.description"
                                :value="f.id"
                            />
                        </el-select>
                    </div>
                    <div v-if="metodo(pago.payment_method_id)?.reference">
                        <label class="text-muted-foreground text-xs"
                            >Referencia</label
                        >
                        <el-input v-model="pago.referencia" size="small" />
                    </div>
                    <div>
                        <label class="text-muted-foreground text-xs"
                            >Soporte</label
                        >
                        <SupportUpload v-model="pago.support_image" multiple />
                    </div>
                    <div>
                        <el-button
                            size="small"
                            type="danger"
                            plain
                            @click="store.removePago(i)"
                        >
                            Quitar
                        </el-button>
                    </div>
                </div>
            </div>

            <!-- Totales -->
            <div class="flex justify-end">
                <div class="text-right text-sm tabular-nums">
                    <div v-if="informar.tipoPago === 1">
                        <b>Total cuotas:</b>
                        {{ formatNumber(store.totalCuotas) }}
                    </div>

                    <template v-if="store.conSaldo">
                        <div>
                            <b>Saldo a favor disponible:</b>
                            {{ formatNumber(store.saldoDisponible) }}
                        </div>
                        <div class="text-green-600">
                            <b>Saldo que queda:</b>
                            {{ formatNumber(store.saldoRestante) }}
                        </div>
                    </template>

                    <template v-else>
                        <div>
                            <b>Total pagos:</b>
                            {{ formatNumber(store.totalPagos) }}
                        </div>
                        <div
                            v-if="informar.tipoPago === 1"
                            :class="
                                diferencia >= 0
                                    ? 'text-green-600'
                                    : 'text-destructive'
                            "
                        >
                            <b
                                >{{
                                    diferencia > 0
                                        ? 'Excedente (queda como saldo a favor)'
                                        : diferencia === 0
                                          ? 'Diferencia'
                                          : 'Faltan'
                                }}:</b
                            >
                            {{ formatNumber(Math.abs(diferencia)) }}
                        </div>
                        <p
                            v-if="informar.tipoPago === 1 && diferencia < 0"
                            class="text-destructive text-xs"
                        >
                            El pago no puede ser menor que las cuotas elegidas.
                        </p>
                    </template>
                </div>
            </div>
        </div>

        <template #footer>
            <el-button @click="store.cerrarInformar()">Cancelar</el-button>
            <el-button
                type="primary"
                :loading="informarSubmitting"
                :disabled="!puedeRegistrar"
                @click="registrar"
            >
                {{
                    store.conSaldo
                        ? 'Pagar con saldo a favor'
                        : 'Registrar pago'
                }}
            </el-button>
        </template>
    </el-dialog>
</template>

<!--
    La cabecera con el azul de marca de GilenSoft es global (ver
    resources/css/app.css → `.el-dialog__header`), aplica a este diálogo y a
    todos los `<el-dialog>` de la app sin necesidad de estilo por componente.
-->

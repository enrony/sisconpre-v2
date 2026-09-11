<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import SupportUpload from '@/components/paymentReport/SupportUpload.vue';
import { formatNumber } from '@/lib/format';
import { usePaymentReportStore } from '@/stores/paymentReport';

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
} = storeToRefs(store);

const diferencia = computed(() => store.totalPagos - store.totalCuotas);

function metodo(id: number | null) {
    return paymentMethods.value.find((m) => m.id === id);
}

const puedeRegistrar = computed(() => {
    if (!informar.value.clienteId) return false;
    if (store.totalPagos <= 0) return false;
    if (informar.value.tipoPago === 1 && informar.value.cuotas.length === 0) {
        return false;
    }
    return informar.value.dataPayments.every(
        (p) => p.payment_method_id && Number(p.valor_importe) > 0,
    );
});

async function registrar() {
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
        header-class="informar-pago-dialog-header"
        body-class="informar-pago-dialog-body"
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
                        <el-radio :value="2">Saldo a favor</el-radio>
                    </el-radio-group>
                </div>
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
                            <label
                                v-for="d in p.prestamos_dias.filter(
                                    (x) => x.apply && !x.pagado,
                                )"
                                :key="d.id"
                                class="flex items-center gap-2 rounded border p-1 text-xs"
                            >
                                <el-checkbox
                                    :model-value="store.cuotaSeleccionada(d.id)"
                                    @change="store.toggleCuota(d)"
                                />
                                <span
                                    >{{ d.date }} ·
                                    {{ formatNumber(d.cuota) }}</span
                                >
                            </label>
                        </div>
                    </el-collapse-item>
                </el-collapse>
            </div>

            <!-- Métodos de pago -->
            <div>
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
                <div class="text-right text-sm">
                    <div v-if="informar.tipoPago === 1">
                        <b>Total cuotas:</b>
                        {{ formatNumber(store.totalCuotas) }}
                    </div>
                    <div>
                        <b>Total pagos:</b> {{ formatNumber(store.totalPagos) }}
                    </div>
                    <div
                        v-if="informar.tipoPago === 1"
                        :class="
                            diferencia >= 0 ? 'text-green-600' : 'text-red-600'
                        "
                    >
                        <b
                            >{{
                                diferencia >= 0 ? 'Saldo a favor' : 'Faltante'
                            }}:</b
                        >
                        {{ formatNumber(Math.abs(diferencia)) }}
                    </div>
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
                Registrar pago
            </el-button>
        </template>
    </el-dialog>
</template>

<style>
/*
 * Cabecera con el azul de marca de GilenSoft (--primary-blue en
 * gilensoft.com). Sólo este diálogo: el resto de la app usa el estilo
 * neutro por defecto de element-plus.
 *
 * Sin `scoped`: el `<header>`/`<div class="el-dialog__body">` reales los
 * renderiza `ElDialogContent` dentro de un `<Teleport>` propio de
 * element-plus, así que el atributo `data-v-*` de este componente nunca
 * llega a esos nodos y un `:deep()` con scope no matchea nada (comprobado en
 * producción: el CSS estaba servido bien pero la regla no aplicaba). En
 * cambio `header-class`/`body-class` son props documentadas de `el-dialog`
 * que element-plus aplica directo sobre esos nodos, así que apuntamos a esas
 * clases con selectores normales.
 */
.informar-pago-dialog-header {
    /*
     * `.el-dialog` trae su propio padding (16px por defecto) alrededor de
     * TODO su contenido — header incluido. Con sólo el color de fondo, la
     * cabecera quedaba "flotando" adentro de ese padding, dejando un marco
     * blanco alrededor (más visible arriba a la derecha, donde vive la "X"
     * de cerrar: se posiciona absoluta contra el diálogo entero, no contra
     * la cabecera). La sangramos hasta el borde real del diálogo.
     */
    margin: calc(var(--el-dialog-padding-primary, 16px) * -1)
        calc(var(--el-dialog-padding-primary, 16px) * -1) 0;
    padding: 1rem 3rem 1rem 1.5rem;
    background-color: #0073c3;
    border-radius: var(--el-dialog-border-radius, var(--radius))
        var(--el-dialog-border-radius, var(--radius)) 0 0;
}

.informar-pago-dialog-header .el-dialog__title {
    color: #fff;
    font-weight: 600;
}

.informar-pago-dialog-header .el-dialog__headerbtn .el-dialog__close {
    color: rgb(255 255 255 / 85%);
}

.informar-pago-dialog-header .el-dialog__headerbtn:hover .el-dialog__close {
    color: #fff;
}

.informar-pago-dialog-body {
    padding-top: 1.25rem;
}
</style>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import CuotasGrid from '@/components/prestamos/CuotasGrid.vue';
import { formatNumber } from '@/lib/format';
import { usePrestamosStore } from '@/stores/prestamos';

const store = usePrestamosStore();
const { modalOpen, form, tables, clientesAll, generado, submitting } =
    storeToRefs(store);

const cuotasAplicables = computed(
    () => form.value.list_pays.filter((c) => c.apply).length,
);

const puedeAvanzar = computed(() => {
    if (form.value.stepActive === 0) return Boolean(form.value.cliente_id);
    return form.value.list_pays.length > 0;
});

const textoBoton = computed(() =>
    form.value.stepActive === 0 ? 'Paso 2' : 'Registrar',
);

function volver() {
    store.generado = false;
    form.value.list_pays = [];
}

function siguiente() {
    if (form.value.stepActive === 1) {
        store.submitPrestamo();
        return;
    }
    form.value.stepActive = 1;
}
</script>

<template>
    <el-dialog
        v-model="modalOpen"
        title="Registrar préstamo"
        width="min(920px, 92vw)"
        :close-on-click-modal="false"
        @closed="store.cerrarModal()"
    >
        <el-steps
            :active="form.stepActive"
            align-center
            finish-status="success"
            class="mb-4"
        >
            <el-step title="Paso 1" description="Cliente" />
            <el-step title="Paso 2" description="Préstamo" />
        </el-steps>

        <!-- Paso 1 -->
        <div v-show="form.stepActive === 0" class="space-y-3">
            <div>
                <label class="text-muted-foreground text-xs font-semibold"
                    >Cliente</label
                >
                <el-select
                    v-model="form.cliente_id"
                    filterable
                    clearable
                    size="small"
                    class="w-full"
                    placeholder="Seleccione el cliente"
                    :disabled="form.stepActive > 0"
                    @change="store.seleccionarCliente()"
                >
                    <el-option
                        v-for="c in clientesAll"
                        :key="c.id"
                        :label="`${c.documento}: ${c.nombre} ${c.apellido ?? ''}`"
                        :value="c.id"
                    />
                </el-select>
            </div>

            <div
                v-if="form.cliente_id && 'documento' in form.clienteSelected"
                class="bg-muted/40 rounded-md border p-3 text-sm"
            >
                <div>
                    <b>Documento:</b>
                    {{ form.clienteSelected.tipo_documento?.sigla }}-{{
                        form.clienteSelected.documento
                    }}
                </div>
                <div>
                    <b>Nombre:</b> {{ form.clienteSelected.nombre }}
                    {{ form.clienteSelected.nombre_segundo }}
                    {{ form.clienteSelected.apellido }}
                    {{ form.clienteSelected.apellido_segundo }}
                </div>
                <div><b>Teléfono:</b> {{ form.clienteSelected.telefono }}</div>
                <div><b>Correo:</b> {{ form.clienteSelected.email }}</div>
                <div>
                    <b>Dirección:</b> {{ form.clienteSelected.direccion }}
                </div>
            </div>
        </div>

        <!-- Paso 2 -->
        <div v-show="form.stepActive === 1" class="space-y-4">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >País</label
                    >
                    <el-select
                        v-model="form.country_id"
                        size="small"
                        class="w-full"
                        :disabled="generado || tables.CountryAll.length <= 1"
                    >
                        <el-option
                            v-for="c in tables.CountryAll"
                            :key="c.id"
                            :label="c.Name"
                            :value="c.id"
                        />
                    </el-select>
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Tipo</label
                    >
                    <el-select
                        v-model="form.tipo_prestamo_id"
                        size="small"
                        class="w-full"
                        :disabled="generado"
                        @change="store.calcularUltimaFecha()"
                    >
                        <el-option
                            v-for="t in tables.TipoPrestamo"
                            :key="t.id"
                            :label="t.descripcion"
                            :value="t.id"
                        />
                    </el-select>
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Frecuencia</label
                    >
                    <el-select
                        v-model="form.tipo_frecuencia_id"
                        size="small"
                        class="w-full"
                        :disabled="generado"
                    >
                        <el-option
                            v-for="t in tables.TipoPrestamo"
                            :key="t.id"
                            :label="t.descripcion"
                            :value="t.id"
                        />
                    </el-select>
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Monto solicitado</label
                    >
                    <el-input-number
                        v-model="form.monto_prestamo"
                        controls-position="right"
                        size="small"
                        class="!w-full"
                        :min="0"
                        :disabled="generado"
                    />
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Tasa %</label
                    >
                    <el-input-number
                        v-model="form.tasa"
                        controls-position="right"
                        size="small"
                        class="!w-full"
                        :min="1"
                        :max="100"
                        :disabled="generado"
                    />
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Pago 1ª cuota</label
                    >
                    <el-date-picker
                        v-model="form.date_first_pay"
                        type="date"
                        size="small"
                        class="!w-full"
                        value-format="YYYY-MM-DD"
                        :disabled="generado"
                        @change="store.calcularUltimaFecha()"
                    />
                </div>
                <div>
                    <label class="text-muted-foreground text-xs font-semibold"
                        >Última cuota</label
                    >
                    <el-date-picker
                        v-model="form.date_last_pay"
                        type="date"
                        size="small"
                        class="!w-full"
                        value-format="YYYY-MM-DD"
                        :disabled="generado"
                    />
                </div>
                <div class="flex flex-col justify-end">
                    <el-checkbox
                        v-model="form.incluir_festivos"
                        :disabled="generado"
                    >
                        Incluir festivos
                    </el-checkbox>
                    <el-checkbox
                        v-model="form.incluir_domingos"
                        :disabled="generado"
                    >
                        Incluir domingos
                    </el-checkbox>
                </div>
                <div class="flex items-end">
                    <el-button
                        type="primary"
                        size="small"
                        :disabled="!store.puedeGenerar"
                        @click="store.generar()"
                    >
                        Generar
                    </el-button>
                    <el-button v-if="generado" size="small" @click="volver"
                        >Rehacer</el-button
                    >
                </div>
            </div>

            <CuotasGrid :lista="form.list_pays" />

            <div v-if="generado" class="bg-muted/40 rounded-md border p-3">
                <el-checkbox v-model="form.apply_surcharge"
                    >Aplicar recargo por mora</el-checkbox
                >
                <div
                    v-if="form.apply_surcharge"
                    class="mt-2 grid grid-cols-2 gap-3 md:grid-cols-3"
                >
                    <div>
                        <label
                            class="text-muted-foreground text-xs font-semibold"
                            >Días para % de mora</label
                        >
                        <el-input-number
                            v-model="form.days_apply_surcharge"
                            controls-position="right"
                            size="small"
                            class="!w-full"
                            :min="0"
                            :max="20"
                        />
                    </div>
                    <div>
                        <label
                            class="text-muted-foreground text-xs font-semibold"
                            >% de mora</label
                        >
                        <el-input-number
                            v-model="form.surcharge"
                            controls-position="right"
                            size="small"
                            class="!w-full"
                            :min="0"
                            :max="100"
                        />
                    </div>
                    <div class="flex flex-col justify-end">
                        <el-checkbox v-model="form.incluir_festivos_surcharge"
                            >Incluir festivos</el-checkbox
                        >
                        <el-checkbox v-model="form.incluir_domingos_surcharge"
                            >Incluir domingos</el-checkbox
                        >
                    </div>
                </div>
            </div>

            <div v-if="generado" class="flex justify-end">
                <div class="text-right text-sm">
                    <div><b>Total cuotas:</b> {{ cuotasAplicables }}</div>
                    <div>
                        <b>Monto:</b> {{ formatNumber(form.monto_prestamo) }}
                    </div>
                    <div>
                        <b>Tasa ({{ form.tasa }}%):</b>
                        {{ formatNumber(form.utilidad) }}
                    </div>
                    <div class="font-bold">
                        <b>Total a pagar:</b> {{ formatNumber(form.total) }}
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <el-button @click="store.cerrarModal()">Cancelar</el-button>
            <el-button v-if="form.stepActive === 1 && generado" @click="volver"
                >Atrás</el-button
            >
            <el-button
                type="primary"
                :loading="submitting"
                :disabled="!puedeAvanzar || submitting"
                @click="siguiente"
            >
                {{ textoBoton }}
            </el-button>
        </template>
    </el-dialog>
</template>

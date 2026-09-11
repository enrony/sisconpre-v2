<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import InformarPagoModal from '@/components/paymentReport/InformarPagoModal.vue';
import PrestamoFormModal from '@/components/prestamos/PrestamoFormModal.vue';
import PrestamosFilters from '@/components/prestamos/PrestamosFilters.vue';
import PrestamosTable from '@/components/prestamos/PrestamosTable.vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import { usePaymentReportStore } from '@/stores/paymentReport';
import { usePrestamosStore } from '@/stores/prestamos';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Préstamos', href: '/prestamos' },
];

const store = usePrestamosStore();
const paymentReportStore = usePaymentReportStore();

onMounted(() => {
    void store.fetchList(1);
    void store.fetchEstados();
});
</script>

<template>
    <Head title="Préstamos" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                title="Listado de préstamos"
                description="Préstamos registrados"
            />
            <div class="flex gap-2">
                <el-button
                    v-if="
                        can('payment_report.registrar') ||
                        can('payment_report.gestionar-informe-de-pago')
                    "
                    type="success"
                    @click="paymentReportStore.abrirInformar()"
                >
                    Informar un pago
                </el-button>
                <el-button
                    v-if="can('prestamos.registrar')"
                    type="primary"
                    @click="store.abrirModal()"
                >
                    Registrar nuevo préstamo
                </el-button>
            </div>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4">
                <PrestamosFilters />
            </div>
            <PrestamosTable />
        </div>

        <PrestamoFormModal />
        <InformarPagoModal />
    </div>
</template>

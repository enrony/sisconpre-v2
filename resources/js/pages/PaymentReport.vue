<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import PaymentReportFilters from '@/components/paymentReport/PaymentReportFilters.vue';
import PaymentReportTable from '@/components/paymentReport/PaymentReportTable.vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import { usePaymentReportStore } from '@/stores/paymentReport';

const store = usePaymentReportStore();

onMounted(() => {
    void store.fetchList(1);
    void store.fetchEstados();
});
</script>

<template>
    <Head title="Informes de pago" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                title="Informes de pago"
                description="Pagos reportados por los clientes"
            />
            <el-button
                v-if="can('payment_report.registrar')"
                type="primary"
                disabled
            >
                Informar un pago
            </el-button>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4">
                <PaymentReportFilters />
            </div>
            <PaymentReportTable />
        </div>
    </div>
</template>

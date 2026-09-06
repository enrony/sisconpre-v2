<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import ClienteFormModal from '@/components/clientes/ClienteFormModal.vue';
import ClientesTable from '@/components/clientes/ClientesTable.vue';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import { type ClienteRow, useClientesStore } from '@/stores/clientes';
import type { Paginated } from '@/stores/prestamos';

const page = usePage();
const store = useClientesStore();

const paginator = computed(
    () => (page.props.lista ?? null) as Paginated<ClienteRow> | null,
);

const term = ref(new URLSearchParams(window.location.search).get('term') ?? '');

function recargar(p = 1) {
    router.get(
        '/clientes',
        { page: p, term: term.value || undefined },
        { preserveState: true, preserveScroll: true, only: ['lista'] },
    );
}

let debounce: ReturnType<typeof setTimeout> | undefined;
function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => recargar(1), 300);
}
onBeforeUnmount(() => clearTimeout(debounce));
</script>

<template>
    <Head title="Clientes" />

    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading title="Clientes" description="Clientes registrados" />
            <el-button
                v-if="can('clientes.registrar')"
                type="primary"
                @click="store.abrirModal()"
            >
                Registrar nuevo cliente
            </el-button>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <div class="mb-4 max-w-sm">
                <el-input
                    v-model="term"
                    clearable
                    size="small"
                    placeholder="Buscar por nombre, apellido o documento"
                    @input="onSearch"
                    @clear="recargar(1)"
                />
            </div>

            <ClientesTable :paginator="paginator" @page="recargar" />
        </div>

        <ClienteFormModal :pagina-actual="paginator?.current_page ?? 1" />
    </div>
</template>

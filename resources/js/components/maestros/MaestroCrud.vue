<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ElMessageBox } from 'element-plus';
import { computed, onMounted, ref } from 'vue';
import MaestroFormModal from '@/components/maestros/MaestroFormModal.vue';
import type {
    MaestroConfig,
    MaestroRow,
    MaestroTables,
} from '@/components/maestros/types';
import Heading from '@/components/Heading.vue';
import { can } from '@/lib/can';
import http from '@/lib/http';
import type { Paginated } from '@/stores/prestamos';

const props = defineProps<{ config: MaestroConfig }>();

const page = usePage();
const tables = ref<MaestroTables>({});

onMounted(async () => {
    if (props.config.tablesUrl) {
        const { data } = await http.get(props.config.tablesUrl);
        tables.value = data ?? {};
    }
});

function lookup(
    row: MaestroRow,
    prop: string,
    lookupKey: string,
    lookupLabel = 'description',
): unknown {
    const id = row[prop];
    const found = (tables.value[lookupKey] ?? []).find(
        (o) => String(o.id) === String(id),
    );
    return found ? (found[lookupLabel] ?? id) : id;
}

const paginator = computed(
    () =>
        (page.props[props.config.pageProp ?? 'lista'] ??
            null) as Paginated<MaestroRow> | null,
);
const currentPage = computed(() => paginator.value?.current_page ?? 1);

const modalOpen = ref(false);
const editing = ref<MaestroRow | null>(null);

const puedeCrear = computed(() => can(`${props.config.resource}.registrar`));
const puedeEditar = computed(() => can(`${props.config.resource}.editar`));
const puedeEliminar = computed(() => can(`${props.config.resource}.eliminar`));

function nuevo() {
    editing.value = null;
    modalOpen.value = true;
}

function editar(row: MaestroRow) {
    editing.value = row;
    modalOpen.value = true;
}

function eliminar(row: MaestroRow) {
    void ElMessageBox.confirm(
        `¿Eliminar ${props.config.singular} "${String(row.description ?? row.id)}"?`,
        'Confirmar',
        { type: 'warning', confirmButtonText: 'Sí', cancelButtonText: 'No' },
    ).then(() => {
        router.delete(
            `/${props.config.resource}/${row.id}/${currentPage.value}`,
            {
                preserveScroll: true,
            },
        );
    });
}

function irAPagina(p: number) {
    router.get(
        `/${props.config.resource}`,
        { page: p },
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <div class="px-4 py-6">
        <div class="mb-4 flex items-center justify-between">
            <Heading
                :title="config.title"
                :description="`Listado de ${config.title.toLowerCase()}`"
            />
            <el-button v-if="puedeCrear" type="primary" @click="nuevo">
                Nuevo {{ config.singular }}
            </el-button>
        </div>

        <div class="bg-card rounded-xl border p-4 shadow-sm">
            <el-table
                :data="paginator?.data ?? []"
                stripe
                border
                size="small"
                style="width: 100%"
                max-height="560"
            >
                <el-table-column
                    prop="id"
                    label="#"
                    width="64"
                    align="center"
                />
                <el-table-column
                    v-for="col in config.columns"
                    :key="col.prop"
                    :prop="col.prop"
                    :label="col.label"
                    :width="col.width"
                    :align="col.align ?? 'left'"
                >
                    <template
                        v-if="col.boolean || col.lookupKey"
                        #default="{ row }"
                    >
                        <template v-if="col.boolean">
                            {{ row[col.prop] ? 'Sí' : 'No' }}
                        </template>
                        <template v-else>
                            {{
                                lookup(
                                    row,
                                    col.prop,
                                    col.lookupKey!,
                                    col.lookupLabel,
                                )
                            }}
                        </template>
                    </template>
                </el-table-column>
                <el-table-column
                    prop="updated"
                    label="Modificado"
                    width="170"
                />
                <el-table-column
                    v-if="puedeEditar || puedeEliminar"
                    label="Operaciones"
                    width="130"
                    align="center"
                    fixed="right"
                >
                    <template #default="{ row }">
                        <div
                            v-if="!row.por_defecto"
                            class="flex justify-center gap-1"
                        >
                            <el-button
                                v-if="puedeEditar"
                                size="small"
                                @click="editar(row)"
                            >
                                Editar
                            </el-button>
                            <el-button
                                v-if="puedeEliminar"
                                size="small"
                                type="danger"
                                plain
                                @click="eliminar(row)"
                            >
                                Eliminar
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <div v-if="paginator" class="mt-4 flex justify-end">
                <el-pagination
                    background
                    layout="total, prev, pager, next"
                    :total="paginator.total"
                    :page-size="paginator.per_page"
                    :current-page="paginator.current_page"
                    @current-change="irAPagina"
                />
            </div>
        </div>

        <MaestroFormModal
            v-model:open="modalOpen"
            :config="config"
            :record="editing"
            :current-page="currentPage"
            :tables="tables"
        />
    </div>
</template>

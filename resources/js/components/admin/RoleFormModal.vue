<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { type ModuloCatalogo, useRolesStore } from '@/stores/roles';

const props = defineProps<{ catalogo: ModuloCatalogo[] }>();

const page = usePage();
const store = useRolesStore();
const { modalOpen, submitting, editing, form } = storeToRefs(store);

const errors = computed(
    () => (page.props.errors ?? {}) as Record<string, string>,
);
const titulo = computed(() => (editing.value ? 'Editar rol' : 'Nuevo rol'));

const seleccionados = computed(() => new Set(form.value.permissions));

function estadoModulo(mod: ModuloCatalogo): boolean | null {
    const n = mod.permisos.filter((p) => seleccionados.value.has(p)).length;
    if (n === 0) return false;
    if (n === mod.permisos.length) return true;
    return null; // indeterminado
}

function ability(name: string): string {
    return name.split('.').slice(1).join('.');
}
</script>

<template>
    <el-dialog
        v-model="modalOpen"
        :title="titulo"
        width="min(760px, 95vw)"
        :close-on-click-modal="false"
        top="6vh"
    >
        <div class="mb-4 max-w-sm">
            <label class="text-muted-foreground text-xs font-semibold"
                >Nombre del rol *</label
            >
            <el-input
                v-model="form.name"
                size="small"
                :disabled="editing?.protegido"
            />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">
                {{ errors.name }}
            </p>
        </div>

        <div class="max-h-[52vh] space-y-2 overflow-y-auto pr-1">
            <div
                v-for="mod in props.catalogo"
                :key="mod.modulo"
                class="rounded-lg border p-3"
            >
                <el-checkbox
                    :model-value="estadoModulo(mod) === true"
                    :indeterminate="estadoModulo(mod) === null"
                    class="font-semibold"
                    @change="store.toggleModulo(mod, $event as boolean)"
                >
                    {{ mod.modulo }}
                </el-checkbox>
                <el-checkbox-group
                    v-model="form.permissions"
                    class="mt-1 flex flex-wrap gap-x-5 gap-y-1 pl-6"
                >
                    <el-checkbox
                        v-for="p in mod.permisos"
                        :key="p"
                        :value="p"
                        size="small"
                    >
                        {{ ability(p) }}
                    </el-checkbox>
                </el-checkbox-group>
            </div>
        </div>

        <template #footer>
            <span class="text-muted-foreground mr-auto text-xs">
                {{ form.permissions.length }} permiso(s)
            </span>
            <el-button @click="store.cerrar()">Cancelar</el-button>
            <el-button
                type="primary"
                :loading="submitting"
                :disabled="!form.name.trim()"
                @click="store.guardar()"
            >
                Guardar
            </el-button>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import http from '@/lib/http';

/**
 * Pedir unirse a otro grupo de trabajo con su código. No entra directo: el
 * dueño del grupo recibe un correo y la aprueba o rechaza.
 */
const open = defineModel<boolean>('open', { default: false });

const codigo = ref('');
const error = ref('');
const enviando = ref(false);

watch(open, (abierto) => {
    if (abierto) {
        codigo.value = '';
        error.value = '';
    }
});

async function enviar() {
    if (!codigo.value.trim()) {
        error.value = 'Ingrese el código del grupo';
        return;
    }
    enviando.value = true;
    error.value = '';
    try {
        const { data } = await http.post('/grupos/solicitudes', {
            codigo: codigo.value.trim(),
        });
        ElNotification.success(data.message);
        open.value = false;
        router.reload({ only: ['gruposTrabajo', 'misSolicitudes'] });
    } catch (e: unknown) {
        const respuesta = (e as { response?: { data?: { message?: string } } })
            .response;
        error.value =
            respuesta?.data?.message ?? 'No se pudo enviar la solicitud';
    } finally {
        enviando.value = false;
    }
}
</script>

<template>
    <el-dialog
        v-model="open"
        title="Unirme a otro grupo de trabajo"
        width="min(420px, 94vw)"
        append-to-body
    >
        <p class="text-muted-foreground mb-3 text-sm">
            Ingrese el código que le compartió el grupo. El dueño del grupo
            recibe un correo para aprobar su ingreso; le avisaremos cuando lo
            resuelva.
        </p>
        <form @submit.prevent="enviar">
            <label
                for="codigo-grupo"
                class="text-muted-foreground mb-1 block text-xs font-semibold"
                >Código del grupo</label
            >
            <el-input
                id="codigo-grupo"
                v-model="codigo"
                placeholder="ABC123"
                maxlength="20"
                autocomplete="off"
                @input="error = ''"
            />
            <p v-if="error" class="text-destructive mt-1.5 text-xs">
                {{ error }}
            </p>
        </form>
        <template #footer>
            <el-button @click="open = false">Cancelar</el-button>
            <el-button type="primary" :loading="enviando" @click="enviar">
                Enviar solicitud
            </el-button>
        </template>
    </el-dialog>
</template>

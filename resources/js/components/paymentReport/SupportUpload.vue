<script setup lang="ts">
import { ElNotification } from 'element-plus';
import { ref } from 'vue';
import {
    fileToSupport,
    SUPPORT_ACCEPT,
    type SupportImage,
    supportError,
} from '@/lib/fileToSupport';

const model = defineModel<SupportImage[]>({ default: () => [] });
withDefaults(defineProps<{ multiple?: boolean; label?: string }>(), {
    multiple: false,
    label: 'Adjuntar soporte',
});

const input = ref<HTMLInputElement | null>(null);

async function onPick(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? []);
    for (const f of files) {
        const err = supportError(f);
        if (err) {
            ElNotification.warning(err);
            continue;
        }
        model.value = [...model.value, await fileToSupport(f)];
    }
    if (input.value) input.value.value = '';
}

function quitar(i: number) {
    model.value = model.value.filter((_, idx) => idx !== i);
}
</script>

<template>
    <div class="space-y-1">
        <input
            ref="input"
            type="file"
            class="hidden"
            :accept="SUPPORT_ACCEPT"
            :multiple="multiple"
            @change="onPick"
        />
        <el-button size="small" @click="input?.click()">
            {{ label }}
        </el-button>
        <ul v-if="model.length" class="space-y-0.5 text-xs">
            <li
                v-for="(s, i) in model"
                :key="i"
                class="flex items-center gap-2"
            >
                <span class="truncate">{{ s.name }}</span>
                <button
                    type="button"
                    class="text-red-500 hover:underline"
                    @click="quitar(i)"
                >
                    quitar
                </button>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import CuotasResumen from '@/components/prestamos/CuotasResumen.vue';
import CuotasSchedule from '@/components/prestamos/CuotasSchedule.vue';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/composables/useIsMobile';
import type { PrestamoRow } from '@/stores/prestamos';

const open = defineModel<boolean>('open', { default: false });
const props = defineProps<{ row: PrestamoRow | null }>();

const isMobile = useIsMobile();

const cliente = computed(() => {
    const c = props.row?.cliente;
    return c ? `${c.nombre ?? ''} ${c.apellido ?? ''}`.trim() : '';
});
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            :side="isMobile ? 'bottom' : 'right'"
            class="w-full gap-0 overflow-y-auto max-md:max-h-[88dvh] max-md:rounded-t-xl sm:max-w-xl"
        >
            <SheetHeader class="border-border border-b pb-3">
                <SheetTitle class="text-base">
                    Préstamo #{{ row?.id }}
                    <span v-if="cliente" class="text-muted-foreground">
                        · {{ cliente }}
                    </span>
                </SheetTitle>
            </SheetHeader>

            <div v-if="row" class="space-y-3 py-4">
                <CuotasResumen :dias="row.prestamos_dias ?? []" />
                <CuotasSchedule :dias="row.prestamos_dias ?? []" />
            </div>
        </SheetContent>
    </Sheet>
</template>

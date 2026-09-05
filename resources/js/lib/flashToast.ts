import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

/**
 * Muestra un toast por cada `flash.toast` que llega en las props compartidas
 * (HandleInertiaRequests) tras una visita Inertia. Los controladores portados
 * del sistema legado usan `session()->flash('flash.message' | 'flash.type')`.
 */
export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const data = page?.props?.flash?.toast as FlashToast | undefined;

        if (data?.message) {
            toast[data.type](data.message);
        }
    });
}

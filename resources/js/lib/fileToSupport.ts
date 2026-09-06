/**
 * Convierte un archivo a la forma que espera el backend para los soportes de
 * informes de pago: `{ name, extension, base64 }` (data URL).
 * El backend (`PaymentReportService`) decodifica `base64` tras la coma.
 */
export interface SupportImage {
    name: string;
    extension: string;
    base64: string;
}

export const SUPPORT_ACCEPT = '.jpg,.jpeg,.png';
export const SUPPORT_MAX_KB = 2048;
const SUPPORT_EXT = ['jpg', 'jpeg', 'png'];

export function supportError(file: File): string | null {
    const ext = (file.name.split('.').pop() ?? '').toLowerCase();
    if (!SUPPORT_EXT.includes(ext)) {
        return 'Solo se permiten imágenes jpg, jpeg o png.';
    }
    if (file.size / 1024 > SUPPORT_MAX_KB) {
        return `La imagen supera el máximo de ${SUPPORT_MAX_KB} KB.`;
    }
    return null;
}

export function fileToSupport(file: File): Promise<SupportImage> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () =>
            resolve({
                name: file.name,
                extension: (file.name.split('.').pop() ?? '').toLowerCase(),
                base64: (reader.result as string) ?? '',
            });
        reader.onerror = () => reject(new Error('No se pudo leer el archivo'));
        reader.readAsDataURL(file);
    });
}

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'scanned']);

const scannerId = 'producto-barcode-scanner';
const errorMessage = ref('');
const scanner = ref(null);
const isStarting = ref(false);

const stopScanner = async () => {
    if (! scanner.value) {
        return;
    }

    try {
        await scanner.value.stop();
    } catch {
        // Scanner already stopped.
    }

    try {
        scanner.value.clear();
    } catch {
        // Scanner already cleared.
    }

    scanner.value = null;
};

const startScanner = async () => {
    errorMessage.value = '';
    isStarting.value = true;

    try {
        await stopScanner();

        scanner.value = new Html5Qrcode(scannerId, {
            formatsToSupport: [
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.QR_CODE,
            ],
            verbose: false,
        });

        await scanner.value.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 280, height: 120 },
                aspectRatio: 1.777778,
            },
            (decodedText) => {
                emit('scanned', decodedText.trim());
                close();
            },
            () => {},
        );
    } catch (error) {
        errorMessage.value = 'No se pudo acceder a la camara. Verifica permisos del navegador.';
    } finally {
        isStarting.value = false;
    }
};

watch(() => props.show, async (visible) => {
    if (visible) {
        await startScanner();
        return;
    }

    await stopScanner();
});

onBeforeUnmount(async () => {
    await stopScanner();
});

const close = async () => {
    await stopScanner();
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" max-width="md" scrollable @close="close">
        <template #title>Escanear codigo de barra</template>
        <template #content>
            <p class="mb-3 text-sm text-stone-500">
                Apunta la camara al codigo de barras o QR del producto.
            </p>
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-stone-900">
                <div :id="scannerId" class="min-h-[220px] w-full" />
            </div>
            <p v-if="isStarting" class="mt-3 text-sm text-amber-700">Iniciando camara...</p>
            <p v-if="errorMessage" class="mt-3 text-sm text-red-600">{{ errorMessage }}</p>
        </template>
        <template #footer>
            <div class="flex justify-end">
                <SecondaryButton @click="close">Cancelar</SecondaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<style>
#producto-barcode-scanner video {
    width: 100% !important;
    border-radius: 0.5rem;
}
</style>

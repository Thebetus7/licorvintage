<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'scanned']);

const containerId = 'barcode-scanner-region';
const scanner = ref(null);
const errorMessage = ref('');
const isStarting = ref(false);
const scanLocked = ref(false);

const formatsToSupport = [
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.CODE_93,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.ITF,
    Html5QrcodeSupportedFormats.CODABAR,
    Html5QrcodeSupportedFormats.QR_CODE,
];

const extractDigits = (rawText) => {
    const digits = String(rawText ?? '').replace(/\D/g, '');
    return digits || null;
};

const playScanBeep = () => {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'square';
        osc.frequency.value = 1400;
        gain.gain.value = 0.15;

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.18);
        osc.stop(ctx.currentTime + 0.18);
    } catch {
        // Audio not available
    }
};

const onScanSuccess = (decodedText) => {
    if (scanLocked.value) {
        return;
    }

    const digits = extractDigits(decodedText);

    if (!digits) {
        return;
    }

    scanLocked.value = true;
    playScanBeep();
    emit('scanned', digits);
    close();
};

const stopScanner = async () => {
    if (!scanner.value) {
        return;
    }

    try {
        const state = scanner.value.getState();

        if (state === 2) {
            await scanner.value.stop();
        }
    } catch {
        // already stopped
    }

    try {
        scanner.value.clear();
    } catch {
        // already cleared
    }

    scanner.value = null;
};

const startScanner = async () => {
    errorMessage.value = '';
    isStarting.value = true;
    scanLocked.value = false;

    try {
        await stopScanner();
        await nextTick();

        scanner.value = new Html5Qrcode(containerId, {
            formatsToSupport,
            verbose: false,
        });

        await scanner.value.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 280, height: 160 },
                disableFlip: false,
            },
            onScanSuccess,
            () => {},
        );
    } catch (error) {
        if (error?.name === 'NotAllowedError' || String(error).includes('NotAllowed')) {
            errorMessage.value = 'Permiso de camara denegado. Habilitalo en la configuracion del navegador.';
        } else if (error?.name === 'NotFoundError' || String(error).includes('NotFound')) {
            errorMessage.value = 'No se encontro ninguna camara.';
        } else {
            errorMessage.value = 'No se pudo iniciar la camara. Recarga e intenta de nuevo.';
        }

        await stopScanner();
    } finally {
        isStarting.value = false;
    }
};

watch(() => props.show, async (visible) => {
    if (visible) {
        await nextTick();
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
    <DialogModal :show="show" max-width="lg" scrollable @close="close">
        <template #title>Escanear codigo de barra</template>
        <template #content>
            <p class="mb-3 text-sm text-stone-500">
                Acerca el codigo de barras a la camara. Se detectara automaticamente.
            </p>
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-stone-900">
                <div :id="containerId" class="scanner-container w-full" />
            </div>
            <div class="mt-2 text-xs">
                <span v-if="isStarting" class="text-amber-700">Iniciando camara...</span>
            </div>
            <p v-if="errorMessage" class="mt-2 text-sm text-red-600">{{ errorMessage }}</p>
        </template>
        <template #footer>
            <div class="flex items-center justify-end">
                <SecondaryButton @click="close">Cancelar</SecondaryButton>
            </div>
        </template>
    </DialogModal>
</template>

<style>
#barcode-scanner-region {
    min-height: 350px;
}

#barcode-scanner-region video {
    width: 100% !important;
    min-height: 350px;
    object-fit: cover;
    border-radius: 0.5rem;
}

#barcode-scanner-region img[alt="Info icon"] {
    display: none !important;
}
</style>

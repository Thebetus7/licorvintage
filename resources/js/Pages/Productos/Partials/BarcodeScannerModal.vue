<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'scanned']);

const videoRef = ref(null);
const errorMessage = ref('');
const infoMessage = ref('');
const isStarting = ref(false);
const isCapturing = ref(false);
const scanLocked = ref(false);
const zoomLabel = ref('');

const stream = ref(null);
const detector = ref(null);
const rafId = ref(null);
const fallbackScanner = ref(null);
const fallbackContainerId = 'producto-barcode-fallback';

const barcodeDetectorFormats = [
    'aztec',
    'code_128',
    'code_39',
    'code_93',
    'codabar',
    'data_matrix',
    'ean_13',
    'ean_8',
    'itf',
    'pdf417',
    'qr_code',
    'upc_a',
    'upc_e',
];

const html5QrcodeFormats = [
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.ITF,
    Html5QrcodeSupportedFormats.CODABAR,
];

const playScanBeep = () => {
    try {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'square';
        oscillator.frequency.value = 1400;
        gain.gain.value = 0.15;

        oscillator.connect(gain);
        gain.connect(context.destination);

        oscillator.start();
        gain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 0.18);
        oscillator.stop(context.currentTime + 0.18);
    } catch {
        // Audio not available.
    }
};

const finishScan = (code) => {
    if (scanLocked.value) {
        return;
    }

    const normalized = String(code ?? '').trim();

    if (! normalized) {
        return;
    }

    scanLocked.value = true;
    playScanBeep();
    emit('scanned', normalized);
    close();
};

const stopRaf = () => {
    if (rafId.value) {
        cancelAnimationFrame(rafId.value);
        rafId.value = null;
    }
};

const stopStream = () => {
    if (stream.value) {
        stream.value.getTracks().forEach((track) => track.stop());
        stream.value = null;
    }
};

const stopFallback = async () => {
    if (! fallbackScanner.value) {
        return;
    }

    try {
        await fallbackScanner.value.stop();
    } catch {
        // already stopped
    }

    try {
        fallbackScanner.value.clear();
    } catch {
        // already cleared
    }

    fallbackScanner.value = null;
};

const teardown = async () => {
    stopRaf();
    stopStream();
    await stopFallback();
    detector.value = null;
};

const applyMaxZoom = async (videoTrack) => {
    try {
        const capabilities = videoTrack.getCapabilities?.();

        if (! capabilities?.zoom) {
            zoomLabel.value = '';
            return;
        }

        const target = Math.min(capabilities.zoom.max ?? 1, Math.max(capabilities.zoom.min ?? 1, 4));

        await videoTrack.applyConstraints({ advanced: [{ zoom: target }] });
        zoomLabel.value = `Zoom optico ${target}x`;
    } catch {
        zoomLabel.value = '';
    }
};

const decodeLoop = async () => {
    if (! detector.value || ! videoRef.value || scanLocked.value) {
        return;
    }

    const video = videoRef.value;

    if (video.readyState >= 2 && video.videoWidth > 0) {
        try {
            const results = await detector.value.detect(video);

            if (results.length > 0) {
                finishScan(results[0].rawValue);
                return;
            }
        } catch {
            // ignore frame errors
        }
    }

    rafId.value = requestAnimationFrame(decodeLoop);
};

const startWithBarcodeDetector = async () => {
    if (! ('BarcodeDetector' in window)) {
        return false;
    }

    let availableFormats = barcodeDetectorFormats;

    try {
        const supported = await window.BarcodeDetector.getSupportedFormats?.();

        if (supported?.length) {
            availableFormats = barcodeDetectorFormats.filter((format) => supported.includes(format));
        }
    } catch {
        // fall back to default formats
    }

    if (! availableFormats.length) {
        return false;
    }

    detector.value = new window.BarcodeDetector({ formats: availableFormats });

    stream.value = await navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1920, min: 1280 },
            height: { ideal: 1080, min: 720 },
        },
    });

    await nextTick();

    const video = videoRef.value;

    if (! video) {
        throw new Error('Video element not ready');
    }

    video.srcObject = stream.value;
    video.setAttribute('playsinline', 'true');
    video.muted = true;
    await video.play();

    const [track] = stream.value.getVideoTracks();
    await applyMaxZoom(track);

    rafId.value = requestAnimationFrame(decodeLoop);
    return true;
};

const startWithHtml5Qrcode = async () => {
    await nextTick();

    fallbackScanner.value = new Html5Qrcode(fallbackContainerId, {
        formatsToSupport: html5QrcodeFormats,
        useBarCodeDetectorIfSupported: true,
        verbose: false,
    });

    await fallbackScanner.value.start(
        { facingMode: { ideal: 'environment' } },
        {
            fps: 15,
            disableFlip: false,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920, min: 1280 },
                height: { ideal: 1080, min: 720 },
            },
        },
        (decodedText) => finishScan(decodedText),
        () => {},
    );

    try {
        const capabilities = fallbackScanner.value.getRunningTrackCapabilities();

        if (capabilities.zoom) {
            const target = Math.min(capabilities.zoom.max ?? 1, Math.max(capabilities.zoom.min ?? 1, 4));
            await fallbackScanner.value.applyVideoConstraints({ advanced: [{ zoom: target }] });
            zoomLabel.value = `Zoom optico ${target}x`;
        }
    } catch {
        // no zoom support
    }
};

const startScanner = async () => {
    errorMessage.value = '';
    infoMessage.value = '';
    zoomLabel.value = '';
    isStarting.value = true;
    scanLocked.value = false;

    try {
        await teardown();
        await nextTick();

        const usedNative = await startWithBarcodeDetector();

        if (! usedNative) {
            infoMessage.value = 'Tu navegador no tiene lector nativo, usando fallback.';
            await startWithHtml5Qrcode();
        }
    } catch (error) {
        if (error?.name === 'NotAllowedError') {
            errorMessage.value = 'Permiso de camara denegado. Habilitalo en la barra de Edge.';
        } else if (error?.name === 'NotFoundError') {
            errorMessage.value = 'No se encontro ninguna camara.';
        } else {
            errorMessage.value = 'No se pudo iniciar la camara. Recarga e intenta de nuevo.';
        }

        await teardown();
    } finally {
        isStarting.value = false;
    }
};

const captureZoomedFrame = (video, zoomFactor) => {
    const sourceWidth = video.videoWidth;
    const sourceHeight = video.videoHeight;

    if (! sourceWidth || ! sourceHeight) {
        throw new Error('Camera not ready');
    }

    const cropWidth = sourceWidth / zoomFactor;
    const cropHeight = sourceHeight / zoomFactor;
    const cropX = (sourceWidth - cropWidth) / 2;
    const cropY = (sourceHeight - cropHeight) / 2;

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(sourceWidth);
    canvas.height = Math.round(sourceHeight);

    canvas.getContext('2d').drawImage(
        video,
        cropX, cropY, cropWidth, cropHeight,
        0, 0, canvas.width, canvas.height,
    );

    return canvas;
};

const canvasToFile = (canvas) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
        if (! blob) {
            reject(new Error('No se pudo capturar la imagen.'));
            return;
        }

        resolve(new File([blob], `barcode-${Date.now()}.jpg`, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.95);
});

const decodeCanvas = async (canvas) => {
    if (detector.value) {
        try {
            const results = await detector.value.detect(canvas);

            if (results[0]?.rawValue) {
                return results[0].rawValue;
            }
        } catch {
            // continue with fallback
        }
    }

    if (fallbackScanner.value) {
        try {
            const file = await canvasToFile(canvas);
            const code = await fallbackScanner.value.scanFile(file, false);

            if (code?.trim()) {
                return code;
            }
        } catch {
            // no decode
        }
    } else {
        try {
            const file = await canvasToFile(canvas);
            const temp = new Html5Qrcode(fallbackContainerId, {
                formatsToSupport: html5QrcodeFormats,
                useBarCodeDetectorIfSupported: true,
                verbose: false,
            });
            const code = await temp.scanFile(file, false);
            try { temp.clear(); } catch { /* noop */ }

            if (code?.trim()) {
                return code;
            }
        } catch {
            // no decode
        }
    }

    return null;
};

const getActiveVideo = () => videoRef.value ?? document.querySelector(`#${fallbackContainerId} video`);

const manualCapture = async () => {
    if (scanLocked.value || isCapturing.value) {
        return;
    }

    const video = getActiveVideo();

    if (! video || ! video.videoWidth) {
        errorMessage.value = 'La camara aun no esta lista.';
        return;
    }

    isCapturing.value = true;
    errorMessage.value = '';

    const zoomLevels = [1, 2, 3, 4.5];

    try {
        for (const zoom of zoomLevels) {
            const canvas = captureZoomedFrame(video, zoom);
            const code = await decodeCanvas(canvas);

            if (code) {
                finishScan(code);
                return;
            }
        }

        errorMessage.value = 'No se detecto codigo. Centra el codigo y vuelve a capturar.';
    } catch {
        errorMessage.value = 'No se detecto codigo. Centra el codigo y vuelve a capturar.';
    } finally {
        isCapturing.value = false;
    }
};

watch(() => props.show, async (visible) => {
    if (visible) {
        await startScanner();
        return;
    }

    await teardown();
});

onBeforeUnmount(async () => {
    await teardown();
});

const close = async () => {
    await teardown();
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" max-width="lg" scrollable @close="close">
        <template #title>Escanear codigo de barra</template>
        <template #content>
            <p class="mb-3 text-sm text-stone-500">
                Acerca el codigo a la camara. Si no lo lee solo, usa <strong>Capturar y leer</strong>.
            </p>
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-stone-900">
                <video
                    ref="videoRef"
                    class="scanner-video w-full"
                    autoplay
                    muted
                    playsinline
                />
                <div :id="fallbackContainerId" class="w-full" />
            </div>
            <div class="mt-2 flex flex-wrap gap-3 text-xs">
                <span v-if="zoomLabel" class="rounded bg-amber-100 px-2 py-0.5 text-amber-800">{{ zoomLabel }}</span>
                <span v-if="isStarting" class="text-amber-700">Iniciando camara...</span>
                <span v-if="isCapturing" class="text-amber-700">Analizando captura con zoom digital...</span>
                <span v-if="infoMessage" class="text-stone-500">{{ infoMessage }}</span>
            </div>
            <p v-if="errorMessage" class="mt-2 text-sm text-red-600">{{ errorMessage }}</p>
        </template>
        <template #footer>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-stone-500">En Edge usamos el lector nativo BarcodeDetector.</p>
                <div class="flex justify-end gap-2">
                    <SecondaryButton @click="close">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        :disabled="isStarting || isCapturing || scanLocked"
                        @click="manualCapture"
                    >
                        Capturar y leer
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<style scoped>
.scanner-video {
    min-height: 400px;
    object-fit: cover;
    background: #0c0c0c;
    border-radius: 0.5rem;
}
</style>

<style>
#producto-barcode-fallback video {
    width: 100% !important;
    min-height: 400px;
    object-fit: cover;
    border-radius: 0.5rem;
}
</style>

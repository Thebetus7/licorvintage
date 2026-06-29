<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    fotos: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    maxFotos: {
        type: Number,
        default: 6,
    },
});

const emit = defineEmits(['update:fotos']);

const page = usePage();
const fileInput = ref(null);
const uploading = ref(false);
const uploadError = ref('');
const showCamera = ref(false);
const videoRef = ref(null);
const streamRef = ref(null);

const canAddMore = () => props.fotos.length < props.maxFotos;

const updateFotos = (fotos) => emit('update:fotos', fotos);

const compressImage = (file, maxWidth = 800, quality = 0.65) => new Promise((resolve, reject) => {
    const image = new Image();
    const objectUrl = URL.createObjectURL(file);

    image.onload = () => {
        const scale = Math.min(1, maxWidth / image.width);
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * scale);
        canvas.height = Math.round(image.height * scale);

        canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
        URL.revokeObjectURL(objectUrl);

        canvas.toBlob(
            (blob) => (blob ? resolve(blob) : reject(new Error('No se pudo comprimir la imagen.'))),
            'image/jpeg',
            quality,
        );
    };

    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error('Archivo de imagen invalido.'));
    };

    image.src = objectUrl;
});

const uploadBlob = async (blob) => {
    const formData = new FormData();
    formData.append('imagen', blob, `producto-${Date.now()}.jpg`);

    const { data } = await window.axios.post(route('productos.imagen.store'), formData, {
        headers: {
            'X-CSRF-TOKEN': page.props.csrf_token,
        },
    });

    return data.url;
};

const addUploadedFile = async (file) => {
    if (! canAddMore()) {
        uploadError.value = `Maximo ${props.maxFotos} fotos por producto.`;
        return;
    }

    uploading.value = true;
    uploadError.value = '';

    try {
        const blob = await compressImage(file);
        const url = await uploadBlob(blob);
        updateFotos([...props.fotos, url]);
    } catch (error) {
        uploadError.value = error.response?.data?.message || error.message || 'Error al subir la imagen.';
    } finally {
        uploading.value = false;
    }
};

const openFilePicker = () => {
    fileInput.value?.click();
};

const onFileSelected = async (event) => {
    const file = event.target.files?.[0];

    if (file) {
        await addUploadedFile(file);
    }

    event.target.value = '';
};

const stopCamera = () => {
    streamRef.value?.getTracks().forEach((track) => track.stop());
    streamRef.value = null;
    showCamera.value = false;
};

const openCamera = async () => {
    uploadError.value = '';

    try {
        streamRef.value = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 800 },
            },
            audio: false,
        });

        showCamera.value = true;

        requestAnimationFrame(() => {
            if (videoRef.value && streamRef.value) {
                videoRef.value.srcObject = streamRef.value;
            }
        });
    } catch {
        uploadError.value = 'No se pudo abrir la camara. Usa importar imagen o revisa permisos.';
    }
};

const capturePhoto = async () => {
    const video = videoRef.value;

    if (! video || ! streamRef.value) {
        return;
    }

    const canvas = document.createElement('canvas');
    const scale = Math.min(1, 800 / video.videoWidth);
    canvas.width = Math.round(video.videoWidth * scale);
    canvas.height = Math.round(video.videoHeight * scale);
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(async (blob) => {
        if (! blob) {
            uploadError.value = 'No se pudo capturar la foto.';
            return;
        }

        stopCamera();
        uploading.value = true;
        uploadError.value = '';

        try {
            const url = await uploadBlob(blob);
            updateFotos([...props.fotos, url]);
        } catch (error) {
            uploadError.value = error.response?.data?.message || 'Error al guardar la foto.';
        } finally {
            uploading.value = false;
        }
    }, 'image/jpeg', 0.65);
};

const removeFoto = (index) => {
    updateFotos(props.fotos.filter((_, itemIndex) => itemIndex !== index));
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <section class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)]/40 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <InputLabel value="Fotos del producto" />
                <p class="mt-0.5 text-xs text-[var(--text-secondary)]/80">JPG comprimido, guardado en /public/media/productos</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <SecondaryButton type="button" :disabled="uploading || !canAddMore()" @click="openCamera">
                    Tomar foto
                </SecondaryButton>
                <SecondaryButton type="button" :disabled="uploading || !canAddMore()" @click="openFilePicker">
                    Importar
                </SecondaryButton>
            </div>
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onFileSelected"
        >

        <div v-if="showCamera" class="mt-3 overflow-hidden rounded-lg border border-amber-200 bg-black">
            <video ref="videoRef" autoplay playsinline class="aspect-video w-full object-cover" />
            <div class="flex gap-2 border-t border-[var(--border-color)] bg-[var(--bg-secondary)] p-2">
                <PrimaryButton type="button" class="flex-1 justify-center" @click="capturePhoto">Capturar</PrimaryButton>
                <SecondaryButton type="button" class="flex-1 justify-center" @click="stopCamera">Cancelar</SecondaryButton>
            </div>
        </div>

        <div v-if="fotos.length" class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
            <div v-for="(foto, index) in fotos" :key="`${foto}-${index}`" class="group relative overflow-hidden rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)]">
                <img :src="foto" :alt="`Foto ${index + 1}`" class="aspect-square w-full object-cover">
                <button
                    type="button"
                    class="absolute right-1 top-1 rounded bg-red-600 px-1.5 py-0.5 text-xs text-white opacity-90"
                    @click="removeFoto(index)"
                >
                    X
                </button>
                <span v-if="index === 0" class="absolute bottom-1 left-1 rounded bg-[#2b1115]/80 px-1.5 py-0.5 text-[10px] text-white">
                    Portada
                </span>
            </div>
        </div>

        <p v-else class="mt-3 rounded-xl border border-dashed border-[var(--border-color)] px-3 py-6 text-center text-xs text-[var(--text-secondary)]">
            Sin fotos. Toma una foto o importa una imagen.
        </p>

        <p v-if="uploading" class="mt-2 text-xs text-amber-700">Subiendo imagen...</p>
        <p v-if="uploadError" class="mt-2 text-xs text-red-600">{{ uploadError }}</p>
        <InputError :message="errors.fotos" class="mt-2" />
    </section>
</template>

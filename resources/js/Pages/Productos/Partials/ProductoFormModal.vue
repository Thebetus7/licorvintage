<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import BarcodeScannerModal from '@/Pages/Productos/Partials/BarcodeScannerModal.vue';
import ProductoFotosPicker from '@/Pages/Productos/Partials/ProductoFotosPicker.vue';

const props = defineProps({
    show: Boolean,
    producto: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);

const showScanner = ref(false);

const form = useForm({
    nombre: '',
    mililitros: 750,
    costo: 0,
    precio_venta: 0,
    descripcion: '',
    imagen: '',
    fotos: [],
    codigo_barra: '',
    publicado: true,
    stock: {
        stock: 0,
        min: 0,
        max: 0,
    },
});

const modalTitle = computed(() => props.producto ? 'Editar producto' : 'Nuevo producto');
const isEditing = computed(() => Boolean(props.producto));

const qrUrl = (codigo) => `https://api.qrserver.com/v1/create-qr-code/?size=96x96&data=${encodeURIComponent(codigo)}`;

const resetForm = () => {
    form.reset();
    form.fotos = [];
    form.publicado = true;
    form.stock = { stock: 0, min: 0, max: 0 };
};

const fillForm = (producto) => {
    form.nombre = producto.nombre;
    form.mililitros = producto.mililitros;
    form.costo = producto.costo;
    form.precio_venta = producto.precio_venta;
    form.descripcion = producto.descripcion || '';
    form.imagen = producto.imagen || '';
    form.fotos = producto.fotos?.length ? [...producto.fotos] : (producto.imagen ? [producto.imagen] : []);
    form.codigo_barra = producto.codigo_barra;
    form.publicado = producto.publicado ?? true;
    form.stock = {
        stock: producto.stock_actual?.stock || 0,
        min: producto.stock_actual?.min || 0,
        max: producto.stock_actual?.max || 0,
    };
};

watch(() => props.show, (visible) => {
    if (! visible) {
        return;
    }

    if (props.producto) {
        fillForm(props.producto);
        return;
    }

    resetForm();
});

const onScanned = (code) => {
    form.codigo_barra = code;
};

const submit = () => {
    form.imagen = form.fotos[0] || '';

    if (props.producto) {
        form.put(route('productos.update', props.producto.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
        return;
    }

    form.post(route('productos.store'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};

const close = () => emit('close');
</script>

<template>
    <DialogModal :show="show" max-width="lg" scrollable @close="close">
        <template #title>{{ modalTitle }}</template>

        <template #content>
            <div class="space-y-5">
                <ProductoFotosPicker
                    v-model:fotos="form.fotos"
                    :errors="form.errors"
                />

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Identificacion</h3>
                    <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                        <div class="sm:col-span-2">
                            <InputLabel value="Nombre" />
                            <TextInput v-model="form.nombre" class="mt-1 block w-full max-w-md" />
                            <InputError :message="form.errors.nombre" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Codigo de barra" />
                            <div class="mt-1 flex max-w-md gap-2">
                                <TextInput v-model="form.codigo_barra" class="w-full" placeholder="EAN / CODE128" />
                                <SecondaryButton type="button" class="shrink-0 whitespace-nowrap" @click="showScanner = true">
                                    Escanear
                                </SecondaryButton>
                            </div>
                            <InputError :message="form.errors.codigo_barra" class="mt-1" />
                        </div>
                        <div v-if="form.codigo_barra" class="flex items-end pb-1">
                            <img :src="qrUrl(form.codigo_barra)" alt="QR" class="h-16 w-16 rounded border border-stone-200">
                        </div>
                        <div>
                            <InputLabel value="Mililitros" />
                            <TextInput v-model="form.mililitros" type="number" class="mt-1 block w-28" />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Precios</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Costo" />
                            <TextInput v-model="form.costo" type="number" step="0.01" class="mt-1 block w-full max-w-[9rem]" />
                        </div>
                        <div>
                            <InputLabel value="Precio venta" />
                            <TextInput v-model="form.precio_venta" type="number" step="0.01" class="mt-1 block w-full max-w-[9rem]" />
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Descripcion</h3>
                    <textarea
                        v-model="form.descripcion"
                        rows="3"
                        class="block w-full max-w-lg rounded-md border-stone-300 text-sm shadow-sm"
                        placeholder="Notas del producto..."
                    />
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Stock</h3>
                    <div v-if="!isEditing" class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel value="Stock inicial" />
                            <TextInput v-model="form.stock.stock" type="number" class="mt-1 block w-full max-w-[7rem]" />
                        </div>
                        <div>
                            <InputLabel value="Minimo" />
                            <TextInput v-model="form.stock.min" type="number" class="mt-1 block w-full max-w-[7rem]" />
                        </div>
                        <div>
                            <InputLabel value="Maximo" />
                            <TextInput v-model="form.stock.max" type="number" class="mt-1 block w-full max-w-[7rem]" />
                        </div>
                    </div>
                    <div v-else class="space-y-3 text-sm text-[var(--text-secondary)]">
                        <p>
                            Stock actual:
                            <strong class="text-[var(--accent)]">{{ form.stock.stock }}</strong>
                            <span class="text-xs text-[var(--text-secondary)] opacity-75"> (solo cambia via inventario)</span>
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <InputLabel value="Minimo" />
                                <TextInput v-model="form.stock.min" type="number" class="mt-1 block w-full max-w-[7rem]" />
                            </div>
                            <div>
                                <InputLabel value="Maximo" />
                                <TextInput v-model="form.stock.max" type="number" class="mt-1 block w-full max-w-[7rem]" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </template>

        <template #footer>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    class="flex min-w-0 items-center gap-3 rounded-lg border px-3 py-2 text-left transition"
                    :class="form.publicado
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900'
                        : 'border-stone-300 bg-white text-stone-600'"
                    @click="form.publicado = !form.publicado"
                >
                    <span
                        class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition"
                        :class="form.publicado ? 'bg-emerald-500' : 'bg-stone-300'"
                    >
                        <span
                            class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition"
                            :class="form.publicado ? 'left-[1.35rem]' : 'left-0.5'"
                        />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold">
                            {{ form.publicado ? 'Visible en catalogo' : 'Oculto del catalogo' }}
                        </span>
                        <span class="block text-xs opacity-80">
                            {{ form.publicado ? 'Los clientes pueden ver este producto' : 'Solo visible en panel interno' }}
                        </span>
                    </span>
                </button>

                <div class="flex shrink-0 justify-end gap-2">
                    <SecondaryButton @click="close">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
                </div>
            </div>
        </template>
    </DialogModal>

    <BarcodeScannerModal
        :show="showScanner"
        @close="showScanner = false"
        @scanned="onScanned"
    />
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    show: Boolean,
    compra: {
        type: Object,
        default: null,
    },
    productos: Array,
    proveedores: Array,
});

const emit = defineEmits(['close']);

const search = ref('');

const form = useForm({
    proveedor_id: '',
    detalles: [],
});

const modalTitle = computed(() => props.compra ? 'Editar compra' : 'Nueva compra');

const filteredProducts = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (! term) {
        return [];
    }

    return props.productos.filter((producto) =>
        producto.nombre.toLowerCase().includes(term)
        || producto.codigo_barra.toLowerCase().includes(term),
    );
});

const showSearchResults = computed(() => search.value.trim().length > 0);

const lineSubtotal = (detalle) => Number(detalle.cantidad || 0) * Number(detalle.precio_unitario || 0);

const totalCompra = computed(() =>
    form.detalles.reduce((sum, item) => sum + lineSubtotal(item), 0),
);

const productoName = (id) => props.productos.find((producto) => producto.id === Number(id))?.nombre || 'Producto';

const resetForm = () => {
    form.reset();
    form.detalles = [];
    search.value = '';
};

const mapDetalleFromServer = (detalle) => ({
    producto_id: detalle.producto_id,
    cantidad: detalle.cantidad,
    precio_unitario: detalle.cantidad > 0
        ? Number(detalle.sub_costo) / Number(detalle.cantidad)
        : 0,
});

const fillForm = (compra) => {
    form.proveedor_id = compra.proveedor_id || '';
    form.detalles = compra.detalle_compras.map(mapDetalleFromServer);
    search.value = '';
};

watch(() => props.show, (visible) => {
    if (! visible) {
        return;
    }

    if (props.compra) {
        fillForm(props.compra);
        return;
    }

    resetForm();
});

const addProducto = (producto) => {
    const exists = form.detalles.some((detalle) => detalle.producto_id === producto.id);
    if (exists) {
        return;
    }

    form.detalles.push({
        producto_id: producto.id,
        cantidad: 1,
        precio_unitario: Number(producto.costo || 0),
    });

    search.value = '';
};

const removeDetalle = (index) => {
    form.detalles.splice(index, 1);
};

const buildPayload = () => ({
    proveedor_id: form.proveedor_id,
    detalles: form.detalles.map((detalle) => ({
        producto_id: detalle.producto_id,
        cantidad: Number(detalle.cantidad),
        sub_costo: lineSubtotal(detalle),
    })),
});

const submit = () => {
    form.transform(() => buildPayload());

    if (props.compra) {
        form.put(route('compras.update', props.compra.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
        return;
    }

    form.post(route('compras.store'), {
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
                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Proveedor</h3>
                    <div>
                        <InputLabel value="Proveedor (opcional)" />
                        <select
                            v-model="form.proveedor_id"
                            class="mt-1 block w-full max-w-md rounded-md border-stone-300 text-sm shadow-sm"
                        >
                            <option value="">Sin proveedor</option>
                            <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                {{ proveedor.nombre }}
                            </option>
                        </select>
                        <InputError :message="form.errors.proveedor_id" class="mt-1" />
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Buscar producto</h3>
                    <div>
                        <InputLabel value="Nombre o codigo de barra" />
                        <TextInput
                            v-model="search"
                            class="mt-1 block w-full max-w-md"
                            placeholder="Escribe para buscar..."
                        />
                    </div>
                    <div
                        v-if="showSearchResults"
                        class="max-h-44 overflow-y-auto rounded-md border border-stone-200"
                    >
                        <button
                            v-for="producto in filteredProducts"
                            :key="producto.id"
                            type="button"
                            class="flex w-full justify-between border-b border-stone-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-amber-50"
                            @click="addProducto(producto)"
                        >
                            <span>{{ producto.nombre }}</span>
                            <span class="text-stone-500">
                                Stock {{ producto.stock_actual?.stock || 0 }} · Bs {{ Number(producto.costo || 0).toFixed(2) }}
                            </span>
                        </button>
                        <p v-if="!filteredProducts.length" class="px-3 py-4 text-center text-sm text-stone-500">
                            No se encontraron productos.
                        </p>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-amber-800">Lista de compra</h3>

                    <p v-if="!form.detalles.length" class="rounded-md border border-dashed border-stone-200 px-4 py-6 text-center text-sm text-stone-500">
                        Agrega productos desde el buscador.
                    </p>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(detalle, index) in form.detalles"
                            :key="index"
                            class="rounded-md border border-stone-200 p-3"
                        >
                            <div class="mb-3 flex items-start justify-between gap-2">
                                <div class="min-w-0 font-medium text-[#2b1115]">
                                    {{ productoName(detalle.producto_id) }}
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-sm text-red-700 hover:text-red-800"
                                    @click="removeDetalle(index)"
                                >
                                    Quitar
                                </button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-[7rem_9rem_1fr]">
                                <div>
                                    <InputLabel value="Cantidad" />
                                    <TextInput
                                        v-model="detalle.cantidad"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full max-w-[7rem]"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Precio unit." />
                                    <TextInput
                                        v-model="detalle.precio_unitario"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="mt-1 block w-full max-w-[9rem]"
                                    />
                                </div>
                                <div class="flex flex-col justify-end">
                                    <span class="text-xs text-stone-500">Subtotal</span>
                                    <span class="text-sm font-semibold text-[#2b1115]">
                                        Bs {{ lineSubtotal(detalle).toFixed(2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.detalles" class="mt-1" />
                </section>
            </div>
        </template>

        <template #footer>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm">
                    <span class="text-stone-500">Total compra:</span>
                    <span class="ms-2 text-base font-semibold text-[#2b1115]">Bs {{ totalCompra.toFixed(2) }}</span>
                </div>
                <div class="flex shrink-0 justify-end gap-2">
                    <SecondaryButton @click="close">Cancelar</SecondaryButton>
                    <PrimaryButton
                        :disabled="form.processing || !form.detalles.length"
                        @click="submit"
                    >
                        Guardar
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
    compra: {
        type: Object,
        default: null,
    },
    proveedores: {
        type: Array,
        required: true,
    },
    productos: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close']);

const search = ref('');
const showSearchResults = ref(false);

const form = useForm({
    proveedor_id: '',
    detalles: [],
});

const modalTitle = computed(() => props.compra ? 'Ver detalles de compra' : 'Registrar compra');

// Filtrar productos por búsqueda
const filteredProducts = computed(() => {
    const query = search.value.toLowerCase().trim();
    if (! query) {
        return [];
    }
    return props.productos.filter(p =>
        p.nombre.toLowerCase().includes(query) ||
        (p.codigo_barra && p.codigo_barra.toLowerCase().includes(query)),
    );
});

// Auto-abrir resultados al escribir
watch(search, (val) => {
    showSearchResults.value = val.length > 0;
});

const addProducto = (producto) => {
    // Evitar duplicados
    const exists = form.detalles.some(d => d.producto_id === producto.id);
    if (exists) {
        search.value = '';
        showSearchResults.value = false;
        return;
    }

    form.detalles.push({
        producto_id: producto.id,
        cantidad: 1,
        precio_unitario: Number(producto.costo || 0),
        fecha_expiracion: '',
    });

    search.value = '';
    showSearchResults.value = false;
};

const removeDetalle = (index) => {
    form.detalles.splice(index, 1);
};

const productoName = (id) => {
    const p = props.productos.find(prod => prod.id === id);
    return p ? p.nombre : 'Producto desconocido';
};

const lineSubtotal = (detalle) => {
    return (Number(detalle.cantidad) || 0) * (Number(detalle.precio_unitario) || 0);
};

const totalCompra = computed(() => {
    return form.detalles.reduce((acc, d) => acc + lineSubtotal(d), 0);
});

const resetForm = () => {
    form.reset();
    search.value = '';
};

watch(() => props.show, (visible) => {
    if (! visible) {
        return;
    }

    resetForm();
});

const submit = () => {
    form.post(route('compras.store'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};

const close = () => emit('close');
</script>

<template>
    <DialogModal :show="show" max-width="lg" scrollable @close="close">
        <template #title>
            <span class="text-[var(--text-primary)] font-bold">{{ modalTitle }}</span>
        </template>

        <template #content>
            <div class="space-y-5">
                <section class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-[var(--accent)]">Proveedor</h3>
                    <div>
                        <label for="proveedor_select" class="block text-sm font-semibold text-[var(--text-secondary)]">Proveedor (Opcional)</label>
                        <select
                            id="proveedor_select"
                            v-model="form.proveedor_id"
                            class="mt-1 block w-full max-w-md rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)] text-sm px-3 py-2.5 focus:outline-none transition"
                        >
                            <option value="" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Sin proveedor</option>
                            <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">
                                {{ proveedor.nombre }}
                            </option>
                        </select>
                        <InputError :message="form.errors.proveedor_id" class="mt-1" />
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-[var(--accent)]">Buscar producto</h3>
                    <div>
                        <label for="product_search" class="block text-sm font-semibold text-[var(--text-secondary)]">Nombre o código de barra</label>
                        <input
                            id="product_search"
                            v-model="search"
                            type="text"
                            class="mt-1 block w-full max-w-md rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] placeholder-[var(--text-secondary)]/50 focus:border-[var(--accent)] focus:ring-[var(--accent)] text-sm px-3 py-2.5 focus:outline-none transition"
                            placeholder="Escribe para buscar..."
                        />
                    </div>
                    <div
                        v-if="showSearchResults"
                        class="max-h-44 overflow-y-auto rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)] shadow-lg"
                    >
                        <button
                            v-for="producto in filteredProducts"
                            :key="producto.id"
                            type="button"
                            class="flex w-full justify-between border-b border-[var(--border-color)] px-3 py-2.5 text-left text-sm last:border-b-0 hover:bg-[var(--bg-primary)]/80 text-[var(--text-primary)] transition"
                            @click="addProducto(producto)"
                        >
                            <span>{{ producto.nombre }}</span>
                            <span class="text-[var(--text-secondary)]/80 text-xs">
                                Stock {{ producto.stock_actual?.stock || 0 }} · Bs {{ Number(producto.costo || 0).toFixed(2) }}
                            </span>
                        </button>
                        <p v-if="!filteredProducts.length" class="px-3 py-4 text-center text-sm text-[var(--text-secondary)]/60">
                            No se encontraron productos.
                        </p>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-[var(--accent)]">Lista de compra</h3>

                    <p v-if="!form.detalles.length" class="rounded-xl border border-dashed border-[var(--border-color)] px-4 py-6 text-center text-sm text-[var(--text-secondary)]/70 bg-[var(--bg-primary)]/30">
                        Agrega productos desde el buscador.
                    </p>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(detalle, index) in form.detalles"
                            :key="index"
                            class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)]/30 p-4"
                        >
                            <div class="mb-3 flex items-start justify-between gap-2">
                                <div class="min-w-0 font-bold text-[var(--text-primary)]">
                                    {{ productoName(detalle.producto_id) }}
                                </div>
                                <button
                                    type="button"
                                    class="shrink-0 text-sm text-rose-500 hover:text-rose-400 font-semibold transition"
                                    @click="removeDetalle(index)"
                                >
                                    Quitar
                                </button>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-[5rem_6rem_9rem_1fr] items-end">
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--text-secondary)]">Cantidad</label>
                                    <input
                                        v-model="detalle.cantidad"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)] text-sm px-3 py-2 focus:outline-none transition"
                                    />
                                    <InputError :message="form.errors[`detalles.${index}.cantidad`]" class="mt-1" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--text-secondary)]">Precio u.</label>
                                    <input
                                        v-model="detalle.precio_unitario"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="mt-1 block w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)] text-sm px-3 py-2 focus:outline-none transition"
                                    />
                                    <InputError :message="form.errors[`detalles.${index}.precio_unitario`]" class="mt-1" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[var(--text-secondary)]">Vencimiento</label>
                                    <input
                                        v-model="detalle.fecha_expiracion"
                                        type="date"
                                        class="mt-1 block w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)] text-xs px-3 py-2 focus:outline-none transition"
                                        required
                                    />
                                    <InputError :message="form.errors[`detalles.${index}.fecha_expiracion`]" class="mt-1" />
                                </div>
                                <div class="flex flex-col justify-end pb-2">
                                    <span class="text-xs text-[var(--text-secondary)]">Subtotal</span>
                                    <span class="text-sm font-semibold text-[var(--text-primary)]">
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between w-full">
                <div class="text-sm">
                    <span class="text-[var(--text-secondary)]">Total compra:</span>
                    <span class="ms-2 text-lg font-bold text-[var(--accent)]">Bs {{ totalCompra.toFixed(2) }}</span>
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

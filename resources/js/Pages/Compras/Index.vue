<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    compras: Object,
    productos: Array,
    proveedores: Array,
});

const showCompraModal = ref(false);
const showProveedorModal = ref(false);
const editingCompra = ref(null);
const editingProveedor = ref(null);
const search = ref('');

const compraForm = useForm({
    proveedor_id: '',
    detalles: [],
});

const proveedorForm = useForm({
    nombre: '',
    telefono: '',
    descripcion: '',
});

const filteredProducts = computed(() => props.productos.filter((producto) => {
    const term = search.value.toLowerCase();
    return producto.nombre.toLowerCase().includes(term) || producto.codigo_barra.toLowerCase().includes(term);
}));

const totalCompra = computed(() => compraForm.detalles.reduce((sum, item) => sum + Number(item.sub_costo || 0), 0));

const openCompra = () => {
    editingCompra.value = null;
    compraForm.reset();
    compraForm.detalles = [];
    showCompraModal.value = true;
};

const editCompra = (compra) => {
    editingCompra.value = compra;
    compraForm.proveedor_id = compra.proveedor_id || '';
    compraForm.detalles = compra.detalle_compras.map((detalle) => ({
        producto_id: detalle.producto_id,
        cantidad: detalle.cantidad,
        sub_costo: detalle.sub_costo,
    }));
    showCompraModal.value = true;
};

const addProducto = (producto) => {
    compraForm.detalles.push({
        producto_id: producto.id,
        cantidad: 1,
        sub_costo: Number(producto.costo || 0),
    });
};

const productoName = (id) => props.productos.find((producto) => producto.id === Number(id))?.nombre || 'Producto';

const saveCompra = () => {
    if (editingCompra.value) {
        compraForm.put(route('compras.update', editingCompra.value.id), {
            preserveScroll: true,
            onSuccess: () => showCompraModal.value = false,
        });
        return;
    }

    compraForm.post(route('compras.store'), {
        preserveScroll: true,
        onSuccess: () => showCompraModal.value = false,
    });
};

const deleteCompra = (compra) => {
    if (confirm(`Eliminar compra #${compra.id}?`)) {
        compraForm.delete(route('compras.destroy', compra.id), { preserveScroll: true });
    }
};

const openProveedor = () => {
    editingProveedor.value = null;
    proveedorForm.reset();
    showProveedorModal.value = true;
};

const editProveedor = (proveedor) => {
    editingProveedor.value = proveedor;
    proveedorForm.nombre = proveedor.nombre;
    proveedorForm.telefono = proveedor.telefono;
    proveedorForm.descripcion = proveedor.descripcion || '';
    showProveedorModal.value = true;
};

const saveProveedor = () => {
    if (editingProveedor.value) {
        proveedorForm.put(route('proveedores.update', editingProveedor.value.id), {
            preserveScroll: true,
            onSuccess: () => showProveedorModal.value = false,
        });
        return;
    }

    proveedorForm.post(route('proveedores.store'), {
        preserveScroll: true,
        onSuccess: () => showProveedorModal.value = false,
    });
};

const deleteProveedor = (proveedor) => {
    if (confirm(`Eliminar proveedor ${proveedor.nombre}?`)) {
        proveedorForm.delete(route('proveedores.destroy', proveedor.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Compras">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[#2b1115]">Compras</h1>
                    <p class="text-sm text-stone-500">Compras con multiples productos y proveedor opcional.</p>
                </div>
                <div class="flex gap-2">
                    <SecondaryButton @click="openProveedor">Proveedores</SecondaryButton>
                    <PrimaryButton @click="openCompra">Nueva Compra</PrimaryButton>
                </div>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-[1fr_320px] sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-stone-50 text-left text-xs font-semibold uppercase text-stone-500">
                        <tr>
                            <th class="px-4 py-3">Compra</th>
                            <th class="px-4 py-3">Proveedor</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Productos</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-sm">
                        <tr v-for="compra in compras.data" :key="compra.id">
                            <td class="px-4 py-3">#{{ compra.id }}</td>
                            <td class="px-4 py-3">{{ compra.proveedor?.nombre || 'Sin proveedor' }}</td>
                            <td class="px-4 py-3">Bs {{ Number(compra.costo).toFixed(2) }}</td>
                            <td class="px-4 py-3">
                                <span v-for="detalle in compra.detalle_compras" :key="detalle.id" class="mr-2 inline-block rounded bg-amber-50 px-2 py-1 text-xs text-amber-800">
                                    {{ detalle.producto?.nombre }} x{{ detalle.cantidad }}
                                </span>
                            </td>
                            <td class="space-x-2 px-4 py-3 text-right">
                                <SecondaryButton @click="editCompra(compra)">Editar</SecondaryButton>
                                <DangerButton @click="deleteCompra(compra)">Eliminar</DangerButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex flex-wrap gap-2 border-t bg-stone-50 px-4 py-3">
                    <Link v-for="link in compras.links" :key="link.label" :href="link.url || '#'" class="rounded-md px-3 py-1 text-sm" :class="link.active ? 'bg-[#2b1115] text-white' : 'bg-white'" v-html="link.label" />
                </div>
            </div>

            <aside class="rounded-lg border bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold text-[#2b1115]">Proveedores</h2>
                    <button class="text-sm text-amber-700" @click="openProveedor">Nuevo</button>
                </div>
                <div class="space-y-2">
                    <div v-for="proveedor in proveedores" :key="proveedor.id" class="rounded-md border border-stone-200 p-3">
                        <div class="font-medium">{{ proveedor.nombre }}</div>
                        <div class="text-xs text-stone-500">{{ proveedor.telefono }}</div>
                        <div class="mt-2 flex gap-2">
                            <button class="text-xs text-amber-700" @click="editProveedor(proveedor)">Editar</button>
                            <button class="text-xs text-red-700" @click="deleteProveedor(proveedor)">Eliminar</button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <DialogModal :show="showCompraModal" @close="showCompraModal = false">
            <template #title>{{ editingCompra ? 'Editar Compra' : 'Nueva Compra' }}</template>
            <template #content>
                <div>
                    <InputLabel value="Proveedor opcional" />
                    <select v-model="compraForm.proveedor_id" class="mt-1 w-full rounded-md border-stone-300">
                        <option value="">Sin proveedor</option>
                        <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">{{ proveedor.nombre }}</option>
                    </select>
                </div>

                <div class="mt-5">
                    <InputLabel value="Buscar producto" />
                    <TextInput v-model="search" class="mt-1 w-full" placeholder="Nombre o codigo" />
                    <div class="mt-3 max-h-44 overflow-y-auto rounded-md border">
                        <button v-for="producto in filteredProducts" :key="producto.id" class="flex w-full justify-between border-b px-3 py-2 text-left text-sm hover:bg-amber-50" @click="addProducto(producto)">
                            <span>{{ producto.nombre }}</span>
                            <span class="text-stone-500">Stock {{ producto.stock_actual?.stock || 0 }}</span>
                        </button>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <h3 class="font-semibold text-[#2b1115]">Lista de compra</h3>
                    <div v-for="(detalle, index) in compraForm.detalles" :key="index" class="grid gap-3 rounded-md border p-3 sm:grid-cols-[1fr_90px_120px_40px]">
                        <div class="font-medium">{{ productoName(detalle.producto_id) }}</div>
                        <TextInput v-model="detalle.cantidad" type="number" min="1" />
                        <TextInput v-model="detalle.sub_costo" type="number" min="0" step="0.01" />
                        <button class="text-red-700" @click="compraForm.detalles.splice(index, 1)">X</button>
                    </div>
                    <div class="text-right font-semibold">Total: Bs {{ totalCompra.toFixed(2) }}</div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showCompraModal = false">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="compraForm.processing" @click="saveCompra">Guardar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showProveedorModal" @close="showProveedorModal = false">
            <template #title>{{ editingProveedor ? 'Editar Proveedor' : 'Nuevo Proveedor' }}</template>
            <template #content>
                <div class="grid gap-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="proveedorForm.nombre" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Telefono" />
                        <TextInput v-model="proveedorForm.telefono" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Descripcion" />
                        <textarea v-model="proveedorForm.descripcion" class="mt-1 w-full rounded-md border-stone-300"></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showProveedorModal = false">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="proveedorForm.processing" @click="saveProveedor">Guardar</PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    productos: Object,
});

const showModal = ref(false);
const editingProduct = ref(null);

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

const modalTitle = computed(() => editingProduct.value ? 'Editar Producto' : 'Crear Producto');
const isEditing = computed(() => Boolean(editingProduct.value));

const qrUrl = (codigo) => `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(codigo)}`;

const portada = (producto) => {
    if (producto.fotos?.length) {
        return producto.fotos[0];
    }

    return producto.imagen;
};

const openCreate = () => {
    editingProduct.value = null;
    form.reset();
    form.fotos = [];
    form.publicado = true;
    form.stock = { stock: 0, min: 0, max: 0 };
    showModal.value = true;
};

const openEdit = (producto) => {
    editingProduct.value = producto;
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
    showModal.value = true;
};

const addFoto = () => {
    if (form.fotos.length < 6) {
        form.fotos.push('');
    }
};

const removeFoto = (index) => {
    form.fotos.splice(index, 1);
};

const submit = () => {
    if (editingProduct.value) {
        form.put(route('productos.update', editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => showModal.value = false,
        });
        return;
    }

    form.post(route('productos.store'), {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const destroy = (producto) => {
    if (confirm(`Eliminar ${producto.nombre}?`)) {
        form.delete(route('productos.destroy', producto.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Productos">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-[#2b1115]">Productos</h1>
                    <p class="text-sm text-stone-500">Catalogo, codigos de barra, fotos y precios de venta.</p>
                </div>
                <PrimaryButton @click="openCreate">Crear Producto</PrimaryButton>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg border border-stone-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase text-stone-500">
                            <tr>
                                <th class="px-4 py-3">Producto</th>
                                <th class="px-4 py-3">Codigo</th>
                                <th class="px-4 py-3">Costo prom.</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Stock</th>
                                <th class="px-4 py-3">Publicado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 text-sm">
                            <tr v-for="producto in productos.data" :key="producto.id">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="portada(producto)" :src="portada(producto)" :alt="producto.nombre" class="h-10 w-10 rounded object-cover">
                                        <div>
                                            <div class="font-semibold">{{ producto.nombre }}</div>
                                            <div class="text-xs text-stone-500">{{ producto.mililitros }} ml</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ producto.codigo_barra }}</div>
                                    <img :src="qrUrl(producto.codigo_barra)" alt="QR" class="mt-1 h-8 w-8">
                                </td>
                                <td class="px-4 py-3">Bs {{ Number(producto.costo_promedio ?? producto.costo).toFixed(2) }}</td>
                                <td class="px-4 py-3">Bs {{ Number(producto.precio_venta).toFixed(2) }}</td>
                                <td class="px-4 py-3">
                                    {{ producto.stock_actual?.stock || 0 }}
                                    <span class="text-xs text-stone-500">min {{ producto.stock_actual?.min || 0 }} / max {{ producto.stock_actual?.max || 0 }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded px-2 py-1 text-xs" :class="producto.publicado ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600'">
                                        {{ producto.publicado ? 'Si' : 'No' }}
                                    </span>
                                </td>
                                <td class="space-x-2 px-4 py-3 text-right">
                                    <SecondaryButton @click="openEdit(producto)">Editar</SecondaryButton>
                                    <DangerButton @click="destroy(producto)">Eliminar</DangerButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap gap-2 border-t bg-stone-50 px-4 py-3">
                    <Link
                        v-for="link in productos.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded-md px-3 py-1 text-sm"
                        :class="link.active ? 'bg-[#2b1115] text-white' : 'bg-white text-stone-700'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <DialogModal :show="showModal" @close="showModal = false">
            <template #title>{{ modalTitle }}</template>
            <template #content>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="form.nombre" class="mt-1 w-full" />
                        <InputError :message="form.errors.nombre" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Codigo de barra" />
                        <TextInput v-model="form.codigo_barra" class="mt-1 w-full" />
                        <InputError :message="form.errors.codigo_barra" class="mt-1" />
                        <img v-if="form.codigo_barra" :src="qrUrl(form.codigo_barra)" alt="QR preview" class="mt-2 h-20 w-20">
                    </div>
                    <div>
                        <InputLabel value="Mililitros" />
                        <TextInput v-model="form.mililitros" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Imagen URL (legacy)" />
                        <TextInput v-model="form.imagen" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Costo" />
                        <TextInput v-model="form.costo" type="number" step="0.01" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Precio venta" />
                        <TextInput v-model="form.precio_venta" type="number" step="0.01" class="mt-1 w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Descripcion" />
                        <textarea v-model="form.descripcion" class="mt-1 w-full rounded-md border-stone-300 shadow-sm"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="form.publicado" type="checkbox" class="rounded border-stone-300">
                            Publicado en catalogo
                        </label>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <InputLabel value="Fotos (URLs)" />
                        <button type="button" class="text-sm text-amber-700" @click="addFoto">Agregar foto</button>
                    </div>
                    <div v-for="(foto, index) in form.fotos" :key="index" class="mt-2 flex gap-2">
                        <TextInput v-model="form.fotos[index]" class="w-full" placeholder="https://..." />
                        <SecondaryButton @click="removeFoto(index)">X</SecondaryButton>
                    </div>
                    <InputError :message="form.errors.fotos" class="mt-1" />
                </div>

                <h3 class="mt-6 border-t border-amber-200 pt-4 text-sm font-bold uppercase text-amber-700">Stock</h3>
                <div v-if="!isEditing" class="mt-3 grid gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Stock inicial" />
                        <TextInput v-model="form.stock.stock" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Minimo" />
                        <TextInput v-model="form.stock.min" type="number" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Maximo" />
                        <TextInput v-model="form.stock.max" type="number" class="mt-1 w-full" />
                    </div>
                </div>
                <div v-else class="mt-3 space-y-2 text-sm text-stone-600">
                    <p>Stock actual: <strong>{{ form.stock.stock }}</strong> (solo cambia via movimientos de inventario)</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Minimo" />
                            <TextInput v-model="form.stock.min" type="number" class="mt-1 w-full" />
                        </div>
                        <div>
                            <InputLabel value="Maximo" />
                            <TextInput v-model="form.stock.max" type="number" class="mt-1 w-full" />
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="form.processing" @click="submit">Guardar</PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

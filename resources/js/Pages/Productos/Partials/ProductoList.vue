<script setup>
import { Link, router } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
    productos: Object,
});

const emit = defineEmits(['edit']);

const qrUrl = (codigo) => `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(codigo)}`;

const portada = (producto) => {
    if (producto.fotos?.length) {
        return producto.fotos[0];
    }

    return producto.imagen;
};

const destroy = (producto) => {
    if (confirm(`Eliminar ${producto.nombre}?`)) {
        router.delete(route('productos.destroy', producto.id), { preserveScroll: true });
    }
};
</script>

<template>
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
                            <SecondaryButton @click="emit('edit', producto)">Editar</SecondaryButton>
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
</template>

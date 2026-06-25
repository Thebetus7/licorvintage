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
    <div class="overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm backdrop-blur-sm transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border-color)]">
                <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs font-semibold uppercase text-[var(--accent)] transition-colors duration-300">
                    <tr>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Costo prom.</th>
                        <th class="px-4 py-3">Precio</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3">Publicado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)] transition-colors duration-300">
                    <tr v-for="producto in productos.data" :key="producto.id" class="hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img v-if="portada(producto)" :src="portada(producto)" :alt="producto.nombre" class="h-10 w-10 rounded object-cover">
                                <div>
                                    <div class="font-semibold text-[var(--text-primary)]">{{ producto.nombre }}</div>
                                    <div class="text-xs text-[var(--text-secondary)]">{{ producto.mililitros }} ml</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-[var(--text-primary)] font-mono text-xs">{{ producto.codigo_barra }}</div>
                            <img :src="qrUrl(producto.codigo_barra)" alt="QR" class="mt-1 h-8 w-8 filter brightness-90">
                        </td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">Bs {{ Number(producto.costo_promedio ?? producto.costo).toFixed(2) }}</td>
                        <td class="px-4 py-3 text-[var(--text-primary)]">Bs {{ Number(producto.precio_venta).toFixed(2) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[var(--text-primary)] font-semibold">{{ producto.stock_actual?.stock || 0 }}</span>
                            <span class="text-xs text-[var(--text-secondary)] block">min {{ producto.stock_actual?.min || 0 }} / max {{ producto.stock_actual?.max || 0 }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2.5 py-1 text-xs font-semibold" :class="producto.publicado ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-stone-800 text-stone-400 border border-stone-700'">
                                {{ producto.publicado ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right whitespace-nowrap">
                            <button @click="emit('edit', producto)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:border-[var(--accent)] hover:text-[var(--text-primary)] transition-all duration-200 cursor-pointer">
                                Editar
                            </button>
                            <DangerButton class="!px-3 !py-1.5 text-xs" @click="destroy(producto)">Eliminar</DangerButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-2 border-t border-[var(--border-color)] bg-[var(--bg-secondary)]/50 px-4 py-3 transition-colors duration-300">
            <Link
                v-for="link in productos.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-md px-3 py-1.5 text-xs font-medium transition-all"
                :class="link.active 
                    ? 'bg-[var(--accent)] text-white shadow-md' 
                    : 'bg-[var(--bg-secondary)]/40 text-[var(--text-secondary)] hover:bg-[var(--bg-secondary)] hover:text-[var(--text-primary)] border border-[var(--border-color)]'"
                v-html="link.label"
            />
        </div>
    </div>
</template>

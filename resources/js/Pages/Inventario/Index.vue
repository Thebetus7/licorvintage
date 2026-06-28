<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioNav from '@/Pages/Inventario/Partials/InventarioNav.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    valorTotal: Number,
    productosBajoMinimo: Number,
    ultimosMovimientos: Array,
    totalProximos: Number,
    totalVencidos: Number,
    productos: Array,
});

const tipoLabel = (tipo) => tipo.replace(/_/g, ' ');
</script>

<template>
    <AppLayout title="Inventario">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-[var(--text-primary)]">Inventario</h1>
                    <p class="text-sm text-[var(--text-secondary)]">Movimientos, costo promedio, conteo y valorización.</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('inventario.salidas.index')" class="px-4 py-2 bg-[var(--bg-secondary)] border border-[var(--border-color)] text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:text-[var(--text-primary)] rounded-md font-semibold text-sm transition cursor-pointer">
                        Gestionar Salidas / Bajas
                    </Link>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <InventarioNav />

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300">
                    <div class="text-sm text-[var(--text-secondary)]">Valor total inventario</div>
                    <div class="mt-1 text-2xl font-bold text-[var(--text-primary)]">Bs {{ Number(valorTotal).toFixed(2) }}</div>
                </div>
                <div class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300">
                    <div class="text-sm text-[var(--text-secondary)]">Productos bajo mínimo</div>
                    <div class="mt-1 text-2xl font-bold text-[var(--accent)]">{{ productosBajoMinimo }}</div>
                </div>
                <div class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-5 shadow-sm text-[var(--text-primary)] transition-colors duration-300">
                    <div class="text-sm text-[var(--text-secondary)]">Alertas de Vencimiento</div>
                    <div class="mt-1 flex gap-4 text-sm font-semibold">
                        <span :class="totalVencidos > 0 ? 'text-rose-400 font-bold' : 'text-[var(--text-secondary)]'">
                            {{ totalVencidos }} vencidos
                        </span>
                        <span :class="totalProximos > 0 ? 'text-amber-400 font-bold' : 'text-[var(--text-secondary)]'">
                            {{ totalProximos }} por vencer
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 shadow-sm transition-colors duration-300">
                <div class="border-b border-[var(--border-color)] bg-[var(--bg-secondary)]/30 px-4 py-3 font-semibold text-[var(--text-primary)]">Últimos movimientos</div>
                <table class="min-w-full divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <thead class="bg-[var(--bg-secondary)]/50 text-left text-xs uppercase text-[var(--accent)]">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Producto</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Cantidad</th>
                            <th class="px-4 py-3">Costo u.</th>
                            <th class="px-4 py-3">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        <tr v-for="mov in ultimosMovimientos" :key="mov.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3 text-xs font-mono">{{ new Date(mov.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 font-semibold text-[var(--text-primary)]">{{ mov.producto?.nombre }}</td>
                            <td class="px-4 py-3 capitalize">{{ tipoLabel(mov.tipo) }}</td>
                            <td class="px-4 py-3">{{ mov.cantidad }}</td>
                            <td class="px-4 py-3">Bs {{ Number(mov.costo_unitario).toFixed(2) }}</td>
                            <td class="px-4 py-3">{{ mov.saldo_cantidad }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

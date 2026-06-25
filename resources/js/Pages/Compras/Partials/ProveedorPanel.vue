<script setup>
defineProps({
    proveedores: Array,
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <aside class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-4 shadow-sm backdrop-blur-sm text-[var(--text-primary)] transition-colors duration-300">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-[var(--text-primary)]">Proveedores</h2>
            <button type="button" class="text-sm text-[var(--accent)] hover:text-[var(--accent-hover)] cursor-pointer font-semibold" @click="emit('create')">
                Nuevo
            </button>
        </div>
        <div class="space-y-2">
            <div
                v-for="proveedor in proveedores"
                :key="proveedor.id"
                class="rounded-md border border-[var(--border-color)] bg-[var(--bg-secondary)]/30 p-3 transition-colors duration-300"
            >
                <div class="font-medium text-[var(--text-primary)]">{{ proveedor.nombre }}</div>
                <div class="text-xs text-[var(--text-secondary)]">{{ proveedor.telefono }}</div>
                <div class="mt-2 flex gap-2">
                    <button type="button" class="text-xs text-[var(--accent)] hover:text-[var(--accent-hover)] cursor-pointer font-semibold" @click="emit('edit', proveedor)">
                        Editar
                    </button>
                    <button type="button" class="text-xs text-red-400 hover:text-red-300 cursor-pointer font-semibold" @click="emit('delete', proveedor)">
                        Eliminar
                    </button>
                </div>
            </div>
            <p v-if="!proveedores.length" class="text-sm text-[var(--text-secondary)]">
                No hay proveedores registrados.
            </p>
        </div>
    </aside>
</template>

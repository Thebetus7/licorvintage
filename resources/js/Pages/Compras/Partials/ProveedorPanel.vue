<script setup>
defineProps({
    proveedores: Array,
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <aside class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-[#2b1115]">Proveedores</h2>
            <button type="button" class="text-sm text-amber-700 hover:text-amber-800" @click="emit('create')">
                Nuevo
            </button>
        </div>
        <div class="space-y-2">
            <div
                v-for="proveedor in proveedores"
                :key="proveedor.id"
                class="rounded-md border border-stone-200 p-3"
            >
                <div class="font-medium">{{ proveedor.nombre }}</div>
                <div class="text-xs text-stone-500">{{ proveedor.telefono }}</div>
                <div class="mt-2 flex gap-2">
                    <button type="button" class="text-xs text-amber-700 hover:text-amber-800" @click="emit('edit', proveedor)">
                        Editar
                    </button>
                    <button type="button" class="text-xs text-red-700 hover:text-red-800" @click="emit('delete', proveedor)">
                        Eliminar
                    </button>
                </div>
            </div>
            <p v-if="!proveedores.length" class="text-sm text-stone-500">
                No hay proveedores registrados.
            </p>
        </div>
    </aside>
</template>

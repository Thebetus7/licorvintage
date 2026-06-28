<script setup>
import { Link } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import EditUserButton from './EditUserButton.vue';

const props = defineProps({
    usuarios: {
        type: Object,
        required: true
    },
    menuItems: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['edit', 'delete']);

const roleName = (user) => user.roles?.[0]?.name || 'sin rol';

const userMenus = (user) => {
    if (!props.menuItems) return [];
    
    if (Array.isArray(user.menus) && user.menus.length > 0) {
        return props.menuItems
            .filter(item => user.menus.includes(item.route_name))
            .map(item => item.label);
    }
    
    const role = roleName(user);
    return props.menuItems
        .filter(item => Array.isArray(item.roles) && item.roles.includes(role))
        .map(item => item.label);
};

const getRoleClass = (role) => {
    switch (role) {
        case 'propietario':
            return 'bg-rose-950/40 text-rose-300 border border-rose-800/30';
        case 'vendedor':
            return 'bg-amber-950/40 text-amber-300 border border-amber-800/30';
        case 'cliente':
            return 'bg-stone-900/60 text-stone-300 border border-stone-800/30';
        default:
            return 'bg-stone-900 text-stone-400';
    }
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 backdrop-blur-md shadow-2xl transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[var(--border-color)]">
                <thead class="bg-[var(--bg-secondary)] text-left text-xs font-semibold uppercase tracking-wider text-[var(--accent)] transition-colors duration-300">
                    <tr>
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Menú Dinámico (BD)</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)] transition-colors duration-300">
                    <tr v-for="user in usuarios.data" :key="user.id" class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 font-semibold text-[var(--text-primary)] align-middle">{{ user.name }}</td>
                        <td class="px-6 py-4 align-middle">{{ user.email }}</td>
                        <td class="px-6 py-4 align-middle">
                            <span class="rounded-full px-3 py-1 text-xs font-medium uppercase" :class="getRoleClass(roleName(user))">
                                {{ roleName(user) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 align-middle">
                            <div class="flex flex-wrap gap-1 max-w-md">
                                <span v-for="menu in userMenus(user)" :key="menu" class="bg-[var(--accent)]/10 text-[var(--accent)] border border-[var(--accent)]/20 rounded px-2 py-0.5 text-xs transition-colors duration-300">
                                    {{ menu }}
                                </span>
                                <span v-if="!userMenus(user).length" class="text-stone-500 text-xs italic">
                                    Sin accesos
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 align-middle">
                            <div class="flex items-center justify-end gap-2">
                                <template v-if="roleName(user) !== 'propietario'">
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-bold rounded-lg border border-indigo-500/30 bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500 hover:text-white transition duration-200 cursor-pointer"
                                        @click="emit('edit', user)"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 text-xs font-bold rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-400 hover:bg-rose-600 hover:text-white transition duration-200 cursor-pointer"
                                        @click="emit('delete', user)"
                                    >
                                        Eliminar
                                    </button>
                                </template>
                                <span v-else class="text-xs text-stone-500 italic pr-4">Sin acciones</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="flex flex-wrap gap-2 border-t border-[var(--border-color)] bg-[var(--bg-secondary)]/50 px-6 py-4 transition-colors duration-300">
            <Link
                v-for="link in usuarios.links"
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

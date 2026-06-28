<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    menuItems: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
});

// Inicializar la matriz con los roles permitidos actualmente
const getInitialMatrix = () => {
    const data = {};
    props.menuItems.forEach(item => {
        // Inicializar array de roles autorizados para este item
        data[item.id] = Array.isArray(item.roles) ? [...item.roles] : [];
    });
    return data;
};

const form = useForm({
    matrix: getInitialMatrix(),
});

const toggleRole = (itemId, roleName) => {
    const rolesList = form.matrix[itemId];
    const index = rolesList.indexOf(roleName);
    
    if (index > -1) {
        // Remover rol
        rolesList.splice(index, 1);
    } else {
        // Agregar rol
        rolesList.push(roleName);
    }
};

const isRoleSelected = (itemId, roleName) => {
    return form.matrix[itemId].includes(roleName);
};

const submit = () => {
    form.post(route('security.matrix.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Éxito al actualizar
        },
    });
};
</script>

<template>
    <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/40 p-6 shadow-xl backdrop-blur-md transition-colors duration-300">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-[var(--border-color)] pb-4 mb-6">
            <div>
                <h2 class="text-xl font-bold text-[var(--text-primary)]">Matriz de Control de Acceso</h2>
                <p class="text-sm text-[var(--text-secondary)] mt-1">
                    Administra qué roles tienen acceso por defecto a cada sección del menú dinámico del sistema.
                </p>
            </div>
            <PrimaryButton 
                @click="submit" 
                :disabled="form.processing"
                class="px-5 py-2.5 bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white font-semibold rounded-lg shadow-lg shadow-black/30 transition cursor-pointer"
            >
                <span v-if="form.processing">Guardando...</span>
                <span v-else>Guardar Cambios</span>
            </PrimaryButton>
        </div>

        <div class="overflow-x-auto rounded-xl border border-[var(--border-color)]">
            <table class="w-full text-left border-collapse bg-stone-950/20">
                <thead>
                    <tr class="border-b border-[var(--border-color)] bg-[var(--bg-secondary)]/50 text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">
                        <th class="px-6 py-4">Módulo / Recurso</th>
                        <th class="px-6 py-4">Ruta Web</th>
                        <th 
                            v-for="role in roles" 
                            :key="role.id" 
                            class="px-6 py-4 text-center"
                        >
                            {{ role.name }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)] text-sm text-[var(--text-secondary)]">
                    <tr 
                        v-for="item in menuItems" 
                        :key="item.id" 
                        class="hover:bg-white/5 transition-colors duration-150"
                    >
                        <td class="px-6 py-4 font-semibold text-[var(--text-primary)]">
                            {{ item.label }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-[var(--accent)]">
                            {{ item.route_name }}
                        </td>
                        <td 
                            v-for="role in roles" 
                            :key="role.id" 
                            class="px-6 py-4 text-center"
                        >
                            <label class="relative inline-flex items-center justify-center cursor-pointer mx-auto">
                                <input 
                                    type="checkbox" 
                                    :checked="isRoleSelected(item.id, role.name)"
                                    @change="toggleRole(item.id, role.name)"
                                    :disabled="role.name === 'propietario'" 
                                    class="sr-only peer"
                                />
                                <!-- Propietario siempre está activo y deshabilitado para edición -->
                                <div 
                                    v-if="role.name === 'propietario'"
                                    class="w-11 h-6 bg-[var(--accent)] rounded-full after:content-[''] after:absolute after:top-0.5 after:left-[20px] after:bg-white after:border-stone-300 after:border after:rounded-full after:h-5 after:w-5 opacity-60 cursor-not-allowed"
                                ></div>
                                <div 
                                    v-else
                                    class="w-11 h-6 bg-stone-850 rounded-full peer peer-focus:ring-2 peer-focus:ring-[var(--accent)]/55 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-stone-500 after:border-stone-400 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--accent)] peer-checked:after:bg-white"
                                ></div>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 text-xs text-[var(--text-secondary)] bg-stone-900/40 p-3 rounded-lg border border-[var(--border-color)]">
            <strong>Nota de Seguridad:</strong> El rol <span class="text-[var(--accent)] font-semibold">propietario</span> posee permisos completos irrevocables sobre todo el sistema para evitar bloqueos accidentales. Los accesos individuales definidos en la ficha de cada usuario sobreescriben esta matriz global.
        </div>
    </div>
</template>

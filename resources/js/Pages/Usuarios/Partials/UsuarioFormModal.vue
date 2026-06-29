<script setup>
import { watch, watchEffect } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    user: {
        type: Object,
        default: null
    },
    menuItems: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'vendedor',
    menus: []
});

const roleName = (user) => user?.roles?.[0]?.name || 'vendedor';

// Escuchar cambios en la propiedad 'user' para rellenar el formulario (Edición)
watch(() => props.user, (newUser) => {
    if (newUser) {
        form.name = newUser.name;
        form.email = newUser.email;
        form.password = '';
        
        const role = roleName(newUser);
        form.role = role;
        
        // Cargar menús individuales de la base de datos o por defecto del rol
        const defaultRoleMenus = props.menuItems
            .filter(item => Array.isArray(item.roles) && item.roles.includes(role))
            .map(item => item.route_name);
            
        form.menus = Array.isArray(newUser.menus) ? [...newUser.menus] : [...defaultRoleMenus];
    } else {
        form.reset();
        form.role = 'vendedor';
        
        // Precargar menús por defecto para el rol predeterminado
        const defaultRoleMenus = props.menuItems
            .filter(item => Array.isArray(item.roles) && item.roles.includes('vendedor'))
            .map(item => item.route_name);
        form.menus = [...defaultRoleMenus];
    }
}, { immediate: true });

// Al cambiar el selector de rol (cuando es creación), actualiza los accesos por defecto
watch(() => form.role, (newRole) => {
    if (!props.user) {
        const defaultRoleMenus = props.menuItems
            .filter(item => Array.isArray(item.roles) && item.roles.includes(newRole))
            .map(item => item.route_name);
        form.menus = [...defaultRoleMenus];
    }
});

const save = () => {
    if (props.user) {
        form.put(route('usuarios.update', props.user.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            }
        });
    } else {
        form.post(route('usuarios.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            }
        });
    }
};

const handleClose = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" @close="handleClose">
        <template #title>
            <span class="text-xl font-bold text-[var(--text-primary)]">
                {{ user ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
            </span>
        </template>
        
        <template #content>
            <div class="grid gap-4 mt-2">
                <div>
                    <InputLabel for="modal-name" value="Nombre" />
                    <TextInput id="modal-name" v-model="form.name" type="text" class="mt-1 w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                
                <div>
                    <InputLabel for="modal-email" value="Correo Electrónico" />
                    <TextInput id="modal-email" v-model="form.email" type="email" class="mt-1 w-full" required />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
                
                <div v-if="!user">
                    <InputLabel for="modal-password" value="Contraseña" />
                    <TextInput id="modal-password" v-model="form.password" type="password" class="mt-1 w-full" required />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                
                <div>
                    <InputLabel for="modal-role" value="Rol de Acceso" />
                    <select id="modal-role" v-model="form.role" class="mt-1 w-full rounded-xl bg-[var(--bg-primary)] border border-[var(--border-color)] text-[var(--text-primary)] focus:border-[var(--accent)] focus:ring-[var(--accent)] py-2.5 px-3 focus:outline-none transition">
                        <option value="propietario" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Propietario</option>
                        <option value="vendedor" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Vendedor</option>
                        <option value="cliente" class="bg-[var(--bg-secondary)] text-[var(--text-primary)]">Cliente</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                </div>

                <!-- Sección de Configuración de Accesos del Menú Dinámico -->
                <div class="mt-4 border-t border-[var(--border-color)] pt-4">
                    <InputLabel value="Accesos del Menú Dinámico (BD)" />
                    <p class="text-xs text-[var(--text-secondary)]/80 mt-0.5 mb-3">Marca las opciones del menú que este usuario tendrá habilitadas.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <label v-for="item in menuItems" :key="item.id" class="flex items-start gap-2.5 cursor-pointer bg-[var(--bg-primary)]/40 border border-[var(--border-color)] hover:border-[var(--accent)]/40 rounded-xl p-2.5 transition">
                            <input
                                v-model="form.menus"
                                type="checkbox"
                                :value="item.route_name"
                                class="mt-1 rounded border-[var(--border-color)] bg-[var(--bg-primary)] text-[var(--accent)] focus:ring-[var(--accent)] focus:ring-offset-[var(--bg-primary)] shadow-sm"
                            >
                            <div class="flex flex-col">
                                <span class="text-sm text-[var(--text-primary)] font-medium select-none">{{ item.label }}</span>
                                <span class="text-[10px] text-[var(--text-secondary)]/80 font-normal mt-0.5 select-none uppercase">
                                    {{ Array.isArray(item.roles) ? item.roles.join(', ') : '' }}
                                </span>
                            </div>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.menus" />
                </div>
            </div>
        </template>
        
        <template #footer>
            <SecondaryButton type="button" @click="handleClose">
                Cancelar
            </SecondaryButton>
            <PrimaryButton type="button" class="ms-3" :disabled="form.processing" @click="save">
                {{ user ? 'Guardar Cambios' : 'Crear Usuario' }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

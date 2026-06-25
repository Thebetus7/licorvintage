<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import UsuarioList from './Partials/UsuarioList.vue';
import UsuarioFormModal from './Partials/UsuarioFormModal.vue';

defineProps({
    usuarios: Object,
    menuItems: Array,
});

const showModal = ref(false);
const editingUser = ref(null);

const deleteForm = useForm({});

const openCreate = () => {
    editingUser.value = null;
    showModal.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingUser.value = null;
};

const destroy = (user) => {
    if (confirm(`¿Eliminar usuario ${user.name}?`)) {
        deleteForm.delete(route('usuarios.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Usuarios">
        <!-- Encabezado Personalizado Elegante -->
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-amber-400 to-[#5a1c24] sm:text-3xl">
                        Gestión de Usuarios
                    </h1>
                    <p class="text-sm text-stone-500 mt-1">
                        Crea y administra los perfiles de vendedores y clientes del negocio.
                    </p>
                </div>
                <PrimaryButton type="button" @click="openCreate">
                    Crear Usuario
                </PrimaryButton>
            </div>
        </template>

        <!-- Contenido principal con el estilo de Welcome -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative">
            <!-- Brillo de fondo para dar look premium -->
            <div class="absolute top-[-100px] left-[10%] w-[300px] h-[300px] rounded-full bg-amber-500/5 blur-[80px] pointer-events-none"></div>

            <div class="relative z-10">
                <UsuarioList
                    :usuarios="usuarios"
                    :menu-items="menuItems"
                    @edit="openEdit"
                    @delete="destroy"
                />
            </div>
        </div>

        <!-- Modal de Formulario -->
        <UsuarioFormModal
            :show="showModal"
            :user="editingUser"
            :menu-items="menuItems"
            @close="closeModal"
        />
    </AppLayout>
</template>

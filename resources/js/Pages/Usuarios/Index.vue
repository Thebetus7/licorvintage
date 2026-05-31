<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    usuarios: Object,
});

const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'vendedor',
});

const roleName = (user) => user.roles?.[0]?.name || 'sin rol';

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.role = 'vendedor';
    showModal.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = roleName(user) === 'cliente' ? 'cliente' : 'vendedor';
    showModal.value = true;
};

const save = () => {
    if (editingUser.value) {
        form.put(route('usuarios.update', editingUser.value.id), {
            preserveScroll: true,
            onSuccess: () => showModal.value = false,
        });
        return;
    }

    form.post(route('usuarios.store'), {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const destroy = (user) => {
    if (confirm(`Eliminar usuario ${user.name}?`)) {
        form.delete(route('usuarios.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Usuarios">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-[#2b1115]">Gestion de Usuarios</h1>
                    <p class="text-sm text-stone-500">Creacion de vendedores y administracion basica.</p>
                </div>
                <PrimaryButton @click="openCreate">Crear vendedor</PrimaryButton>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-stone-50 text-left text-xs font-semibold uppercase text-stone-500">
                        <tr>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 text-sm">
                        <tr v-for="user in usuarios.data" :key="user.id">
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-amber-50 px-2 py-1 text-xs text-amber-800">{{ roleName(user) }}</span>
                            </td>
                            <td class="space-x-2 px-4 py-3 text-right">
                                <SecondaryButton v-if="roleName(user) !== 'propietario'" @click="openEdit(user)">Editar</SecondaryButton>
                                <DangerButton v-if="roleName(user) !== 'propietario'" @click="destroy(user)">Eliminar</DangerButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="flex flex-wrap gap-2 border-t bg-stone-50 px-4 py-3">
                    <Link v-for="link in usuarios.links" :key="link.label" :href="link.url || '#'" class="rounded-md px-3 py-1 text-sm" :class="link.active ? 'bg-[#2b1115] text-white' : 'bg-white'" v-html="link.label" />
                </div>
            </div>
        </div>

        <DialogModal :show="showModal" @close="showModal = false">
            <template #title>{{ editingUser ? 'Editar Usuario' : 'Crear Vendedor' }}</template>
            <template #content>
                <div class="grid gap-4">
                    <div>
                        <InputLabel value="Nombre" />
                        <TextInput v-model="form.name" class="mt-1 w-full" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput v-model="form.email" type="email" class="mt-1 w-full" />
                    </div>
                    <div v-if="!editingUser">
                        <InputLabel value="Password" />
                        <TextInput v-model="form.password" type="password" class="mt-1 w-full" />
                    </div>
                    <div v-if="editingUser">
                        <InputLabel value="Rol" />
                        <select v-model="form.role" class="mt-1 w-full rounded-md border-stone-300">
                            <option value="vendedor">Vendedor</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="form.processing" @click="save">Guardar</PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

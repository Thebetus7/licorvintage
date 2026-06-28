<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name || '',
    ci: '',
    phone: '',
});

const submit = () => {
    form.post(route('profile.complete.store'));
};
</script>

<template>
    <Head title="Completar Perfil" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-stone-200">
                ¡Ya casi estás listo!
            </h2>
            <p class="mt-2 text-sm text-stone-400">
                Para finalizar tu registro, necesitamos completar algunos datos adicionales obligatorios.
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Nombre Completo -->
            <div>
                <InputLabel for="name" value="Nombre Completo" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full bg-stone-900 border-stone-700 text-stone-200 focus:border-amber-500 focus:ring-amber-500"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Carnet de Identidad (CI) -->
            <div class="mt-4">
                <InputLabel for="ci" value="Carnet de Identidad (CI)" />
                <TextInput
                    id="ci"
                    v-model="form.ci"
                    type="text"
                    class="mt-1 block w-full bg-stone-900 border-stone-700 text-stone-200 focus:border-amber-500 focus:ring-amber-500"
                    required
                    placeholder="Ej. 12345678"
                />
                <InputError class="mt-2" :message="form.errors.ci" />
            </div>

            <!-- Teléfono o Celular -->
            <div class="mt-4">
                <InputLabel for="phone" value="Número de Teléfono / Celular" />
                <TextInput
                    id="phone"
                    v-model="form.phone"
                    type="text"
                    class="mt-1 block w-full bg-stone-900 border-stone-700 text-stone-200 focus:border-amber-500 focus:ring-amber-500"
                    required
                    placeholder="Ej. 70000000"
                />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <!-- Botón de Envío -->
            <div class="flex items-center justify-end mt-6">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Completar Registro
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const fillAdmin = () => {
    form.email = 'admin@licorvintage.com';
    form.password = 'password';
};

const fillCliente = () => {
    form.email = 'cliente1@gmail.com';
    form.password = 'password';
};

const fillVendedor = () => {
    form.email = 'javier@licorvintage.com';
    form.password = 'password';
};

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getFirebaseAuth } from '@/firebase';
import { signInWithPopup, GoogleAuthProvider } from 'firebase/auth';

const processingFirebase = ref(false);
const firebaseError = ref('');

const loginWithGoogle = async () => {
    processingFirebase.value = true;
    firebaseError.value = '';

    try {
        const auth = getFirebaseAuth();
        if (!auth) {
            throw new Error('No se pudo inicializar Firebase.');
        }

        const provider = new GoogleAuthProvider();
        const result = await signInWithPopup(auth, provider);
        const idToken = await result.user.getIdToken();

        // Enviar el token al backend de Laravel
        router.post(route('auth.firebase'), {
            idToken: idToken
        }, {
            onError: (errors) => {
                firebaseError.value = errors.email || 'Error al iniciar sesión en el servidor.';
                processingFirebase.value = false;
            },
            onFinish: () => {
                processingFirebase.value = false;
            }
        });
    } catch (error) {
        console.error(error);
        firebaseError.value = 'Error durante la autenticación con Google: ' + (error.code || error.message || error);
        processingFirebase.value = false;
    }
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-[var(--text-secondary)]">Remember me</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <div class="flex flex-col gap-2 me-auto">
                    <SecondaryButton type="button" @click="fillAdmin">
                        Llenar Admin
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="fillVendedor">
                        Llenar Vendedor
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="fillCliente">
                        Llenar Cliente
                    </SecondaryButton>
                </div>

                <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-[var(--text-secondary)] hover:text-[var(--text-primary)] rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--accent)]">
                    Forgot your password?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>

            <div class="mt-6 flex flex-col items-center justify-center gap-4">
                <div class="relative w-full flex items-center justify-center">
                    <div class="border-t border-[var(--border-color)] w-full absolute"></div>
                    <span class="bg-[var(--bg-tertiary)] px-3 text-[var(--text-secondary)] text-xs uppercase tracking-wider relative z-10">O inicia con</span>
                </div>

                <div v-if="firebaseError" class="text-red-500 text-sm font-medium w-full text-center">
                    {{ firebaseError }}
                </div>

                <button type="button" @click="loginWithGoogle" :disabled="processingFirebase" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 border border-[var(--border-color)] rounded-xl text-sm font-medium text-[var(--text-primary)] bg-[var(--bg-secondary)]/50 hover:bg-[var(--bg-secondary)] hover:border-[var(--accent)] hover:text-[var(--accent)] transition duration-150 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] disabled:opacity-50 cursor-pointer">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    <span>{{ processingFirebase ? 'Conectando...' : 'Iniciar con Google' }}</span>
                </button>
            </div>
        </form>
    </AuthenticationCard>
</template>


<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getFirebaseAuth } from '@/firebase';
import { signInWithPopup, GoogleAuthProvider } from 'firebase/auth';

const processingFirebase = ref(false);
const firebaseError = ref('');

const registerWithGoogle = async () => {
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
                firebaseError.value = errors.email || 'Error al registrarse con Google en el servidor.';
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
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nombre" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" required />

                        <div class="ms-2 text-stone-400">
                            I agree to the <a target="_blank" :href="route('terms.show')" class="underline text-sm text-stone-400 hover:text-stone-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="underline text-sm text-stone-400 hover:text-stone-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">Privacy Policy</a>
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link :href="route('login')" class="underline text-sm text-stone-400 hover:text-stone-200 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                    ¿Ya estás registrado?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Registrarse
                </PrimaryButton>
            </div>

            <div class="mt-6 flex flex-col items-center justify-center gap-4">
                <div class="relative w-full flex items-center justify-center">
                    <div class="border-t border-stone-850 w-full absolute"></div>
                    <span class="bg-stone-900 px-3 text-stone-400 text-xs uppercase tracking-wider relative z-10">O regístrate con</span>
                </div>

                <div v-if="firebaseError" class="text-red-500 text-sm font-medium w-full text-center">
                    {{ firebaseError }}
                </div>

                <button type="button" @click="registerWithGoogle" :disabled="processingFirebase" class="w-full flex items-center justify-center gap-3 px-4 py-2.5 border border-stone-750 rounded-md text-sm font-medium text-stone-200 bg-stone-900/50 hover:bg-stone-800 hover:text-white transition duration-150 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                    </svg>
                    <span>{{ processingFirebase ? 'Conectando...' : 'Registrarse con Google' }}</span>
                </button>
            </div>
        </form>
    </AuthenticationCard>
</template>

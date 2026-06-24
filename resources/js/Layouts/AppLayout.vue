<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';

defineProps({
    title: String,
});

const page = usePage();
const showingNavigationDropdown = ref(false);

const roles = computed(() => {
    const value = page.props.auth.roles || [];

    return Array.isArray(value) ? value : Object.values(value);
});

const hasRole = (role) => roles.value.includes(role);
const canOperate = computed(() => hasRole('propietario') || hasRole('vendedor'));

const isNavActive = (routeName) => {
    if (routeName === 'inventario.index') {
        return route().current('inventario.*');
    }

    return route().current(routeName);
};

const navigation = computed(() => [
    { label: 'Dashboard', routeName: 'dashboard', show: canOperate.value },
    { label: 'Productos', routeName: 'productos.index', show: canOperate.value },
    { label: 'Inventario', routeName: 'inventario.index', show: hasRole('propietario') },
    { label: 'Compras', routeName: 'compras.index', show: canOperate.value },
    { label: 'Caja', routeName: 'caja.index', show: canOperate.value },
    { label: 'Usuarios', routeName: 'usuarios.index', show: hasRole('propietario') },
    { label: 'Catalogo', routeName: 'cliente.productos', show: hasRole('cliente') },
].filter((item) => item.show));

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-stone-100 text-stone-900">
            <nav class="border-b border-amber-900/10 bg-[#2b1115] text-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex items-center gap-8">
                            <Link :href="route('dashboard')" class="flex items-center gap-3">
                                <ApplicationMark class="block h-9 w-auto text-amber-300" />
                                <span class="text-lg font-semibold tracking-wide">Licor Vintage</span>
                            </Link>

                            <div class="hidden items-center gap-2 md:flex">
                                <Link
                                    v-for="item in navigation"
                                    :key="item.routeName"
                                    :href="route(item.routeName)"
                                    class="rounded-md px-3 py-2 text-sm font-medium text-stone-200 transition hover:bg-white/10 hover:text-white"
                                    :class="{ 'bg-amber-500 text-stone-950 hover:bg-amber-400': isNavActive(item.routeName) }"
                                >
                                    {{ item.label }}
                                </Link>
                            </div>
                        </div>

                        <div class="hidden items-center gap-4 md:flex">
                            <div class="text-right">
                                <div class="text-sm font-semibold">{{ $page.props.auth.user?.name }}</div>
                                <div class="text-xs text-amber-200">{{ roles.join(', ') }}</div>
                            </div>
                            <button class="rounded-md bg-white/10 px-3 py-2 text-sm hover:bg-white/20" @click="logout">
                                Salir
                            </button>
                        </div>

                        <button class="md:hidden" @click="showingNavigationDropdown = !showingNavigationDropdown">
                            <span class="sr-only">Menu</span>
                            <span class="block h-0.5 w-6 bg-white" />
                            <span class="mt-1.5 block h-0.5 w-6 bg-white" />
                            <span class="mt-1.5 block h-0.5 w-6 bg-white" />
                        </button>
                    </div>
                </div>

                <div v-if="showingNavigationDropdown" class="border-t border-white/10 px-4 py-3 md:hidden">
                    <Link
                        v-for="item in navigation"
                        :key="item.routeName"
                        :href="route(item.routeName)"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-stone-100"
                    >
                        {{ item.label }}
                    </Link>
                    <button class="mt-2 block rounded-md px-3 py-2 text-sm font-medium text-stone-100" @click="logout">
                        Salir
                    </button>
                </div>
            </nav>

            <div v-if="$page.props.flash.success || $page.props.flash.error" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                <div
                    class="rounded-md border px-4 py-3 text-sm shadow-sm"
                    :class="$page.props.flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
                >
                    {{ $page.props.flash.success || $page.props.flash.error }}
                </div>
            </div>

            <header v-if="$slots.header" class="bg-white shadow-sm">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="py-8">
                <slot />
            </main>
        </div>
    </div>
</template>

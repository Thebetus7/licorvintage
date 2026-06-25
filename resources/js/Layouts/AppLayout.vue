<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import AppearanceWidget from '@/Components/AppearanceWidget.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';

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

const navigation = computed(() => {
    return page.props.menu || [];
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-[var(--bg-primary)] text-[var(--text-primary)] relative overflow-hidden flex flex-col justify-between transition-colors duration-300">
            <!-- Luces de fondo premium -->
            <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-[var(--glow-1)] blur-[120px] pointer-events-none transition-colors duration-300"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-[var(--glow-2)] blur-[150px] pointer-events-none transition-colors duration-300"></div>

            <div class="flex-1 flex flex-col relative z-10">
                <nav class="border-b border-[var(--border-color)] bg-[var(--bg-secondary)] text-[var(--text-primary)] transition-colors duration-300">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="flex h-16 justify-between">
                            <div class="flex items-center gap-8">
                                <Link :href="route('dashboard')" class="flex items-center gap-3">
                                    <ApplicationMark class="block h-9 w-auto text-[var(--accent)]" />
                                    <span class="text-lg font-semibold tracking-wide text-[var(--text-primary)]">Licor Vintage</span>
                                </Link>

                                <div class="hidden items-center gap-2 md:flex">
                                    <Link
                                        v-for="item in navigation"
                                        :key="item.routeName"
                                        :href="route(item.routeName)"
                                        class="rounded-md px-3 py-2 text-sm font-medium text-[var(--text-secondary)] transition hover:bg-white/10 hover:text-[var(--text-primary)]"
                                        :class="{ 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]': isNavActive(item.routeName) }"
                                    >
                                        {{ item.label }}
                                    </Link>
                                </div>
                            </div>

                            <!-- Buscador Global -->
                            <div class="hidden md:flex items-center flex-1 max-w-xs mx-4">
                                <GlobalSearch />
                            </div>

                            <div class="hidden items-center gap-4 md:flex">
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-[var(--text-primary)]">{{ $page.props.auth.user?.name }}</div>
                                    <div class="text-xs text-[var(--accent)]">{{ roles.join(', ') }}</div>
                                </div>
                                <button class="rounded-md bg-stone-900 border border-[var(--border-color)] px-3 py-2 text-sm hover:bg-stone-800 text-[var(--text-primary)] cursor-pointer" @click="logout">
                                    Salir
                                </button>
                            </div>

                            <button class="md:hidden text-[var(--text-primary)]" @click="showingNavigationDropdown = !showingNavigationDropdown">
                                <span class="sr-only">Menu</span>
                                <span class="block h-0.5 w-6 bg-current" />
                                <span class="mt-1.5 block h-0.5 w-6 bg-current" />
                                <span class="mt-1.5 block h-0.5 w-6 bg-current" />
                            </button>
                        </div>
                    </div>

                    <div v-if="showingNavigationDropdown" class="border-t border-[var(--border-color)] px-4 py-3 md:hidden bg-[var(--bg-secondary)]">
                        <div class="mb-4">
                            <GlobalSearch />
                        </div>
                        <Link
                            v-for="item in navigation"
                            :key="item.routeName"
                            :href="route(item.routeName)"
                            class="block rounded-md px-3 py-2 text-sm font-medium text-[var(--text-primary)] hover:bg-white/10"
                        >
                            {{ item.label }}
                        </Link>
                        <button class="mt-2 block w-full text-left rounded-md px-3 py-2 text-sm font-medium text-[var(--text-primary)] hover:bg-white/10 cursor-pointer" @click="logout">
                            Salir
                        </button>
                    </div>
                </nav>

                <div v-if="$page.props.flash.success || $page.props.flash.error" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8 w-full">
                    <div
                        class="rounded-md border px-4 py-3 text-sm shadow-sm"
                        :class="$page.props.flash.success ? 'border-emerald-200/20 bg-emerald-950/40 text-emerald-300' : 'border-red-200/20 bg-red-950/40 text-red-300'"
                    >
                        {{ $page.props.flash.success || $page.props.flash.error }}
                    </div>
                </div>

                <header v-if="$slots.header" class="border-b border-[var(--border-color)] bg-[var(--bg-tertiary)]/30 backdrop-blur-md transition-colors duration-300">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main class="py-8 flex-1">
                    <slot />
                </main>

                <!-- Pie de Página Premium con Contador de Visitas -->
                <footer class="border-t border-[var(--border-color)] bg-[var(--bg-secondary)]/50 backdrop-blur-md py-6 text-center text-xs text-[var(--text-secondary)] transition-colors duration-300">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <span>&copy; {{ new Date().getFullYear() }} Licor Vintage - Todos los derechos reservados.</span>
                        <span class="bg-white/5 border border-white/10 px-4 py-1.5 rounded-full flex items-center gap-2 backdrop-blur-sm text-[var(--text-primary)]">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                            </span>
                            Visitas a esta página: <strong class="text-[var(--accent)] font-extrabold ml-1">{{ $page.props.page_views_count }}</strong>
                        </span>
                    </div>
                </footer>
            </div>
        </div>

        <!-- Widget de Apariencia y Accesibilidad -->
        <AppearanceWidget />
    </div>
</template>

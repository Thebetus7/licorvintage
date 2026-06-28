<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppearanceWidget from '@/Components/AppearanceWidget.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Licor Vintage - Boutique & Gestión" />

    <div class="min-h-screen bg-[var(--bg-primary)] text-[var(--text-primary)] flex flex-col justify-between selection:bg-[var(--accent)] selection:text-white relative overflow-hidden transition-colors duration-300">
        <!-- Luces de fondo (Efecto Gradiente Lujoso) -->
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-[var(--glow-1)] blur-[120px] pointer-events-none transition-colors duration-300"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-[var(--glow-2)] blur-[150px] pointer-events-none transition-colors duration-300"></div>

        <!-- Encabezado / Navegación -->
        <header class="relative z-10 mx-auto max-w-7xl w-full px-6 py-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <!-- Logotipo Minimalista Premium -->
                <div class="h-10 w-10 rounded-lg bg-gradient-to-tr from-[var(--accent)] to-[var(--bg-secondary)] flex items-center justify-center shadow-lg shadow-black/30 border border-[var(--border-color)]">
                    <span class="text-xl font-bold text-white">LV</span>
                </div>
                <span class="text-xl font-semibold tracking-wider bg-clip-text text-transparent bg-gradient-to-r from-[var(--accent)] to-[var(--text-primary)] hidden sm:inline">Licor Vintage</span>
            </div>

            <!-- Buscador Global -->
            <div class="flex-1 max-w-md mx-4">
                <GlobalSearch />
            </div>

            <nav v-if="canLogin" class="flex gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="px-5 py-2 rounded-lg bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white font-medium transition duration-200 shadow-md shadow-black/30 text-sm cursor-pointer"
                >
                    Entrar al Sistema
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="px-4 py-2 text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition font-medium text-sm rounded-lg hover:bg-white/5 cursor-pointer"
                    >
                        Iniciar Sesión
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-4 py-2 bg-[var(--bg-secondary)] hover:bg-[var(--bg-tertiary)] text-[var(--accent)] border border-[var(--border-color)] font-medium text-sm rounded-lg transition duration-200 cursor-pointer"
                    >
                        Registrarse
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Contenido Principal -->
        <main class="relative z-10 flex-1 flex flex-col justify-center items-center px-6 py-12 max-w-7xl mx-auto w-full">
            <div class="grid gap-12 lg:grid-cols-2 items-center w-full">
                <!-- Columna Izquierda: Introducción de la empresa -->
                <div class="space-y-8 text-left">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--accent)]/10 border border-[var(--accent)]/20 text-xs font-semibold text-[var(--accent)] tracking-wider uppercase">
                        Licor Vintage • Selecto & Confiable
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-[var(--text-primary)] leading-tight">
                        La forma inteligente de gestionar tu <span class="bg-clip-text text-transparent bg-gradient-to-r from-[var(--accent)] to-[var(--text-primary)]">Licorería</span>
                    </h1>
                    
                    <p class="text-[var(--text-secondary)] text-lg leading-relaxed">
                        Bienvenido a <strong>Licor Vintage</strong>. Somos la solución especializada e integral para la administración del stock, ventas, compras y flujos de caja en el rubro de licores y bebidas. Facilitamos a los propietarios y vendedores el control total de sus productos y ofrecemos a los clientes un catálogo digital dinámico y exclusivo de alta gama.
                    </p>

                    <!-- Acciones rápidas -->
                    <div class="flex flex-wrap gap-4 pt-2">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="px-6 py-3 rounded-lg bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white font-semibold transition duration-200 shadow-lg shadow-black/35 cursor-pointer"
                        >
                            Ir al Panel de Control
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-6 py-3 rounded-lg bg-[var(--accent)] hover:bg-[var(--accent-hover)] text-white font-semibold transition duration-200 shadow-lg shadow-black/35 cursor-pointer"
                            >
                                Acceder al Catálogo
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-6 py-3 rounded-lg bg-[var(--bg-secondary)] border border-[var(--border-color)] hover:bg-[var(--bg-tertiary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] font-semibold transition duration-200 cursor-pointer"
                            >
                                Crear Cuenta
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Columna Derecha: Vista previa o Ilustración Premium -->
                <div class="relative flex justify-center items-center">
                    <!-- Efecto brillo por detrás de la imagen -->
                    <div class="absolute w-[80%] h-[80%] rounded-full bg-[var(--glow-1)] blur-[80px] pointer-events-none transition-colors duration-300"></div>
                    
                    <div class="relative overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-tertiary)]/80 p-4 shadow-2xl shadow-black/80 max-w-md w-full transition-colors duration-300">
                        <div class="aspect-[4/3] rounded-xl overflow-hidden bg-[var(--bg-secondary)] relative group transition-colors duration-300">
                            <!-- Foto premium de un licor elegante o copa -->
                            <img 
                                src="https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=800&auto=format&fit=crop&q=80" 
                                alt="Licor Vintage Exclusivo" 
                                class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-[var(--bg-primary)] via-transparent to-transparent"></div>
                            <div class="absolute bottom-4 left-4 right-4">
                                <span class="text-xs font-semibold uppercase tracking-widest text-[var(--accent)]">Variedad Exclusiva</span>
                                <h3 class="text-lg font-bold text-white mt-1">Los mejores destilados y vinos finos</h3>
                            </div>
                        </div>
                        
                        <!-- Mini estadísticas / ventajas -->
                        <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-[var(--border-color)] text-center">
                            <div>
                                <div class="text-2xl font-bold text-[var(--accent)]">100%</div>
                                <div class="text-xs text-[var(--text-secondary)]">Control de Stock</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-[var(--accent)]">Seguro</div>
                                <div class="text-xs text-[var(--text-secondary)]">Arqueos de Caja</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de características minimalista -->
            <div class="grid gap-6 md:grid-cols-3 w-full mt-24">
                <div class="p-6 rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/30 backdrop-blur-sm hover:border-[var(--accent)]/40 transition duration-300">
                    <div class="h-10 w-10 rounded-lg bg-[var(--accent)]/10 flex items-center justify-center text-[var(--accent)] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">Control de Inventario</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        Controla stock mínimo, máximo, realiza ajustes de inventario de forma rápida y visualiza los movimientos de mercancía (Kardex).
                    </p>
                </div>
                
                <div class="p-6 rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/30 backdrop-blur-sm hover:border-[var(--accent)]/40 transition duration-300">
                    <div class="h-10 w-10 rounded-lg bg-[var(--accent)]/10 flex items-center justify-center text-[var(--accent)] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.214.139a4.865 4.865 0 005.572 0 4.865 4.865 0 000-6.79a4.865 4.865 0 00-5.572 0l-.214.138A4.865 4.865 0 009 13.5H9z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">Gestión de Caja</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        Aperturas, cierres de caja detallados y registro transparente de todos los movimientos de dinero diarios.
                    </p>
                </div>

                <div class="p-6 rounded-xl border border-[var(--border-color)] bg-[var(--bg-secondary)]/30 backdrop-blur-sm hover:border-[var(--accent)]/40 transition duration-300">
                    <div class="h-10 w-10 rounded-lg bg-[var(--accent)]/10 flex items-center justify-center text-[var(--accent)] mb-4">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 00-2.25 2.25v9a2.25 2.25 0 002.25 2.25h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25H15M9 12l3 3m0 0l3-3m-3 3V2.25" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--text-primary)] mb-2">Catálogo Online</h3>
                    <p class="text-[var(--text-secondary)] text-sm leading-relaxed">
                        Ofrece a tus clientes una experiencia de compra fluida mediante un catálogo virtual de bebidas con stock real actualizado.
                    </p>
                </div>
            </div>
        </main>

        <!-- Pie de página -->
        <footer class="relative z-10 border-t border-[var(--border-color)] bg-[var(--bg-secondary)] py-8 text-center text-xs text-[var(--text-secondary)] transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    &copy; 2026 Licor Vintage. Todos los derechos reservados.
                </div>
                <div class="bg-white/5 border border-white/10 px-4 py-1.5 rounded-full flex items-center gap-2 backdrop-blur-sm text-[var(--text-primary)]">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    Visitas a esta página: <strong class="text-[var(--accent)] font-extrabold ml-1">{{ $page.props.page_views_count }}</strong>
                </div>
                <div class="text-[var(--text-secondary)]/70">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </div>
            </div>
        </footer>
    </div>

    <!-- Widget de Accesibilidad y Temas -->
    <AppearanceWidget />
</template>

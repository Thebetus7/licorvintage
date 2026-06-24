<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Customizer from '@/Components/Customizer.vue';

defineProps({
    title: String,
});

const page = usePage();
const showingNavigationDropdown = ref(false);

// Simplified Roles mapping
const roleName = computed(() => page.props.auth.role || '');
const hasRole = (role) => roleName.value.toLowerCase() === role.toLowerCase();
const canOperate = computed(() => hasRole('propietario') || hasRole('vendedor'));

const navigation = computed(() => [
    { label: 'Dashboard', routeName: 'dashboard', icon: 'dashboard', show: canOperate.value },
    { label: 'Productos', routeName: 'productos.index', icon: 'productos', show: canOperate.value },
    { label: 'Inventario', routeName: 'inventario.index', icon: 'inventario', show: hasRole('propietario') },
    { label: 'Compras', routeName: 'compras.index', icon: 'compras', show: canOperate.value },
    { label: 'Caja', routeName: 'caja.index', icon: 'caja', show: canOperate.value },
    { label: 'Usuarios', routeName: 'usuarios.index', icon: 'usuarios', show: hasRole('propietario') },
    { label: 'Catálogo Público', routeName: 'cliente.productos', icon: 'catalogo', show: hasRole('cliente') },
].filter((item) => item.show));

const logout = () => {
    router.post(route('logout'));
};

// --- Themes, Accessibility & Sidebar Moveable State ---
const sidebarPosition = ref('left'); // 'left', 'right', 'top', 'bottom'
const sidebarLocked = ref(true);
const theme = ref('adultos'); // 'adultos', 'jovenes', 'ninos'
const fontSize = ref('mediano'); // 'pequeno', 'mediano', 'grande'
const highContrast = ref(false);
const dayNightMode = ref('dia'); // 'dia', 'noche', 'auto'

const isDragging = ref(false);

// Load settings from localStorage
onMounted(() => {
    const savedPos = localStorage.getItem('sidebar-position');
    if (savedPos) sidebarPosition.value = savedPos;

    const savedLock = localStorage.getItem('sidebar-locked');
    if (savedLock !== null) sidebarLocked.value = savedLock === 'true';

    const savedTheme = localStorage.getItem('pref-theme');
    if (savedTheme) theme.value = savedTheme;

    const savedFontSize = localStorage.getItem('pref-font-size');
    if (savedFontSize) fontSize.value = savedFontSize;

    const savedContrast = localStorage.getItem('pref-contrast');
    if (savedContrast !== null) highContrast.value = savedContrast === 'true';

    const savedDayNightMode = localStorage.getItem('pref-daynight-mode');
    if (savedDayNightMode) {
        dayNightMode.value = savedDayNightMode;
    } else {
        // Fallback/migration check
        const savedAutoDayNight = localStorage.getItem('pref-auto-daynight');
        if (savedAutoDayNight !== null) {
            dayNightMode.value = savedAutoDayNight === 'true' ? 'auto' : 'dia';
        }
    }

    applyPreferences();

    // Sync from Customizer floating panel
    window.addEventListener('preference-updated-from-customizer', (e) => {
        const d = e.detail;
        sidebarPosition.value = d.sidebarPosition;
        sidebarLocked.value = d.sidebarLocked;
        theme.value = d.theme;
        fontSize.value = d.fontSize;
        highContrast.value = d.highContrast;
        dayNightMode.value = d.dayNightMode;
    });
});

// Watch settings and save to localStorage
watch([sidebarPosition, sidebarLocked, theme, fontSize, highContrast, dayNightMode], () => {
    localStorage.setItem('sidebar-position', sidebarPosition.value);
    localStorage.setItem('sidebar-locked', sidebarLocked.value ? 'true' : 'false');
    localStorage.setItem('pref-theme', theme.value);
    localStorage.setItem('pref-font-size', fontSize.value);
    localStorage.setItem('pref-contrast', highContrast.value ? 'true' : 'false');
    localStorage.setItem('pref-daynight-mode', dayNightMode.value);
    applyPreferences();

    // Broadcast update back to Customizer floating panel
    window.dispatchEvent(new CustomEvent('preference-updated', { detail: { key: 'sidebar-position', value: sidebarPosition.value } }));
    window.dispatchEvent(new CustomEvent('preference-updated', { detail: { key: 'sidebar-locked', value: sidebarLocked.value } }));
    window.dispatchEvent(new CustomEvent('preference-updated', { detail: { key: 'pref-daynight-mode', value: dayNightMode.value } }));
});

// Apply classes to document HTML element
const applyPreferences = () => {
    const root = document.documentElement;
    // Clear previous theme, font size, and contrast classes
    root.className = '';
    
    // Theme class
    root.classList.add(`theme-${theme.value}`);
    
    // Font Size class
    root.classList.add(`font-size-${fontSize.value}`);
    
    // High Contrast class
    if (highContrast.value) {
        root.classList.add('high-contrast');
    }

    // Determine if night-active (for theme variable overrides)
    let isNightActive = false;
    if (dayNightMode.value === 'noche') {
        isNightActive = true;
    } else if (dayNightMode.value === 'auto') {
        const hour = new Date().getHours();
        const isNight = hour >= 18 || hour < 6;
        if (isNight) {
            isNightActive = true;
        }
    }

    if (isNightActive) {
        root.classList.add('night-active');
    }

    // Determine if .dark should be added (for Tailwind dark: classes)
    let isDark = false;
    if (highContrast.value) {
        isDark = true;
    } else if (theme.value === 'jovenes') {
        // Jóvenes theme is dark by design in all modes
        isDark = true;
    } else if (isNightActive) {
        isDark = true;
    }

    if (isDark) {
        root.classList.add('dark');
    }
};

// Drag and drop mechanics to move sidebar to closest screen edge
const startDrag = (e) => {
    if (sidebarLocked.value) return;
    isDragging.value = true;
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', stopDrag);
    document.body.classList.add('select-none'); // Prevent text selection
};

const onDrag = (e) => {
    if (!isDragging.value) return;
    const x = e.clientX;
    const y = e.clientY;
    if (!x || !y) return;

    const w = window.innerWidth;
    const h = window.innerHeight;

    // Distances to screen edges
    const distLeft = x;
    const distRight = w - x;
    const distTop = y;
    const distBottom = h - y;

    const minDist = Math.min(distLeft, distRight, distTop, distBottom);

    let newPos = 'left';
    if (minDist === distLeft) newPos = 'left';
    else if (minDist === distRight) newPos = 'right';
    else if (minDist === distTop) newPos = 'top';
    else if (minDist === distBottom) newPos = 'bottom';

    if (sidebarPosition.value !== newPos) {
        sidebarPosition.value = newPos;
    }
};

const stopDrag = () => {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.body.classList.remove('select-none');
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <!-- Layout Wrapper -->
        <div 
            class="min-h-screen bg-slate-100 dark:bg-slate-950 flex transition-colors duration-300"
            :class="{
                'flex-row': sidebarPosition === 'left',
                'flex-row-reverse': sidebarPosition === 'right',
                'flex-col': sidebarPosition === 'top',
                'flex-col-reverse': sidebarPosition === 'bottom'
            }"
        >
            <!-- 
                =========================================
                SIDEBAR MENU / CONTROLLER
                =========================================
            -->
            <aside 
                class="bg-[#0f172a] text-white flex flex-col justify-between border-slate-800 transition-all duration-300 select-none z-40 relative shadow-lg"
                :class="{
                    'w-full md:w-64 min-h-screen border-r': sidebarPosition === 'left',
                    'w-full md:w-64 min-h-screen border-l': sidebarPosition === 'right',
                    'w-full h-auto border-b py-2 px-4 flex-col md:flex-row items-center': sidebarPosition === 'top',
                    'w-full h-auto border-t py-2 px-4 flex-col md:flex-row-reverse items-center': sidebarPosition === 'bottom',
                    'opacity-85 border-dashed border-amber-500': isDragging
                }"
            >
                <!-- Drag handle & header -->
                <div 
                    class="flex items-center justify-between p-4 border-b border-slate-800 w-full"
                    :class="{ 
                        'flex-row': ['left', 'right', 'top', 'bottom'].includes(sidebarPosition),
                        'md:border-b-0 md:py-0 md:w-auto md:gap-4': ['top', 'bottom'].includes(sidebarPosition)
                    }"
                >
                    <div class="flex items-center gap-3">
                        <!-- Drag Handle Icon -->
                        <div 
                            v-if="!sidebarLocked"
                            @mousedown="startDrag"
                            class="cursor-move p-1 bg-slate-850 hover:bg-slate-800 rounded text-xs select-none text-slate-300"
                            title="Arrastra para mover la barra de tareas"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>
                        <span class="font-black text-sm uppercase tracking-wider text-amber-400">Licor Vintage</span>
                    </div>

                    <!-- Lock Button -->
                    <button 
                        @click="sidebarLocked = !sidebarLocked" 
                        class="p-1.5 rounded text-xs font-bold transition flex items-center gap-1 shadow-sm"
                        :class="sidebarLocked ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-[#0f172a] animate-pulse'"
                        :title="sidebarLocked ? 'Barra bloqueada. Desbloquea para mover.' : 'Barra móvil. Arrastra desde el indicador de puntos o usa el menú.'"
                    >
                        <svg v-if="sidebarLocked" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 10v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ sidebarLocked ? 'Bloqueada' : 'Móvil' }}</span>
                    </button>
                </div>

                <!-- Navigation items -->
                <nav 
                    class="flex-grow py-4 px-2 w-full space-y-1 overflow-y-auto"
                    :class="{
                        'flex flex-col': ['left', 'right'].includes(sidebarPosition),
                        'flex flex-row md:flex items-center justify-center gap-2 py-0 px-4 space-y-0 overflow-y-visible': ['top', 'bottom'].includes(sidebarPosition),
                        'hidden md:flex': ['top', 'bottom'].includes(sidebarPosition) && !showingNavigationDropdown
                    }"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.routeName"
                        :href="route(item.routeName)"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all group"
                        :class="
                            route().current(item.routeName)
                                ? 'bg-amber-500 text-[#0f172a] font-bold shadow-md'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                        "
                    >
                        <span class="text-base shrink-0">
                            <svg v-if="item.icon === 'dashboard'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'productos'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V9a4 4 0 014-4h6a4 4 0 014 4v10a2 2 0 01-2 2zM9 5V3a1 1 0 011-1h4a1 1 0 011 1v2"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'inventario'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'compras'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'caja'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'usuarios'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <svg v-else-if="item.icon === 'catalogo'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </span>
                        <span :class="{ 'md:hidden lg:inline': ['top', 'bottom'].includes(sidebarPosition) }">{{ item.label }}</span>
                    </Link>
                </nav>

                <!-- User profile & Logout inside sidebar -->
                <div 
                    class="p-4 border-t border-slate-800 w-full"
                    :class="{
                        'md:border-t-0 md:p-0 md:w-auto': ['top', 'bottom'].includes(sidebarPosition),
                        'hidden md:block': ['top', 'bottom'].includes(sidebarPosition) && !showingNavigationDropdown
                    }"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="truncate max-w-[120px]">
                            <div class="text-xs font-bold truncate text-slate-200">{{ $page.props.auth.user?.nombre }}</div>
                            <div class="text-[10px] text-amber-400 font-semibold truncate">{{ roleName }}</div>
                        </div>
                        <button 
                            @click="logout" 
                            class="bg-slate-800 hover:bg-red-900/40 text-slate-300 hover:text-red-200 p-2.5 rounded-lg text-xs transition flex items-center justify-center"
                            title="Cerrar Sesión"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- 
                =========================================
                MAIN CONTENT AREA
                =========================================
            -->
            <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
                <!-- Session Flash messages -->
                <div v-if="$page.props.flash.success || $page.props.flash.error" class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
                    <div
                        class="rounded-lg border px-4 py-3 text-sm shadow-sm flex justify-between items-center"
                        :class="$page.props.flash.success ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/20 dark:border-emerald-900/30 dark:text-emerald-300' : 'border-red-200 bg-red-50 text-red-800 dark:bg-red-950/20 dark:border-red-900/30 dark:text-red-300'"
                    >
                        <span>{{ $page.props.flash.success || $page.props.flash.error }}</span>
                    </div>
                </div>

                <!-- Main view header slot -->
                <header v-if="$slots.header" class="admin-header bg-white dark:bg-[#0b0f19] border-b border-slate-200 dark:border-slate-900 py-6 transition-colors shadow-sm">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page content slot -->
                <main class="py-8 flex-grow">
                    <slot />
                </main>

                <!-- 
                    =========================================
                    ACCESSIBLE FOOTER WITH PAGE VISITS
                    =========================================
                -->
                <footer class="bg-white dark:bg-[#0b0f19] border-t border-slate-200 dark:border-slate-900 py-4 mt-auto transition-colors">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-405">
                        <div>&copy; 2026 Licor Vintage. Todos los derechos reservados.</div>
                        <div class="bg-slate-100 dark:bg-slate-850 border border-slate-200 dark:border-slate-700 px-3 py-1.5 rounded-lg flex items-center gap-2 text-slate-600 dark:text-slate-300 font-medium">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span>Visitas en esta página:</span>
                            <span class="font-mono font-black text-amber-600 dark:text-amber-400 bg-slate-200 dark:bg-slate-950 px-2 py-0.5 rounded border border-slate-300 dark:border-slate-900">
                                {{ $page.props.visitas_actual || 1 }}
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

        <!-- Floating design and theme customizer component -->
        <Customizer />
    </div>
</template>

<style>
/* =========================================================================
   1. REGISTRO DE FUENTES GOOGLE FONTS & CONFIGURACIÓN GLOBAL
   ========================================================================= */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Space+Grotesk:wght@500;700&family=Fredoka:wght@500;700&display=swap');

/* Dynamic transitions */
html, body {
    transition: font-size 0.2s ease, background-color 0.3s ease, color 0.3s ease;
}
</style>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
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

const flashMessage = ref({
    text: '',
    type: '',
    show: false,
});

let flashTimeout = null;

watch(() => page.props.flash, (newFlash) => {
    if (newFlash && (newFlash.success || newFlash.error)) {
        flashMessage.value.text = newFlash.success || newFlash.error;
        flashMessage.value.type = newFlash.success ? 'success' : 'error';
        flashMessage.value.show = true;

        if (flashTimeout) clearTimeout(flashTimeout);
        flashTimeout = setTimeout(() => {
            flashMessage.value.show = false;
        }, 5000);
    }
}, { deep: true, immediate: true });

// --- ESTADOS Y LÓGICA DE LA BARRA DE TAREAS MOVIBLE ---
const navPosition = ref(localStorage.getItem('nav-position') || 'top');
const isDragging = ref(false);
const draggedPosition = ref('top');
const contextMenu = ref({ show: false, x: 0, y: 0 });

const isVertical = computed(() => navPosition.value === 'left' || navPosition.value === 'right');

// Clases CSS del contenedor principal <nav> (Fixed absoluto, acompaña siempre al usuario)
const navClasses = computed(() => {
    const base = "bg-[var(--bg-secondary)] text-[var(--text-primary)] transition-all duration-300 z-40 select-none shadow-md fixed";
    
    if (navPosition.value === 'top') {
        return `${base} top-0 left-0 w-full h-16 border-b border-[var(--border-color)]`;
    }
    if (navPosition.value === 'bottom') {
        return `${base} bottom-0 left-0 w-full h-16 border-t border-[var(--border-color)]`;
    }
    if (navPosition.value === 'left') {
        return `${base} top-0 left-0 w-full h-16 md:w-64 md:h-screen md:flex md:flex-col md:justify-between md:py-6 md:px-4 border-b md:border-b-0 md:border-r border-[var(--border-color)] md:overflow-hidden`;
    }
    if (navPosition.value === 'right') {
        return `${base} top-0 left-0 md:left-auto md:right-0 w-full h-16 md:w-64 md:h-screen md:flex md:flex-col md:justify-between md:py-6 md:px-4 border-b md:border-b-0 md:border-l border-[var(--border-color)] md:overflow-hidden`;
    }
    return base;
});

// Clases de Relleno (Padding) dinámicas para el contenedor de contenido principal
const contentClasses = computed(() => {
    const base = "flex-1 flex flex-col justify-between min-w-0 transition-all duration-300 pt-16 md:pt-0";
    
    if (navPosition.value === 'top') {
        return `${base} md:pt-16`;
    }
    if (navPosition.value === 'bottom') {
        return `${base} md:pb-16`;
    }
    if (navPosition.value === 'left') {
        return `${base} md:pl-64`;
    }
    if (navPosition.value === 'right') {
        return `${base} md:pr-64`;
    }
    return base;
});

// Manejadores de Arrastre (Drag-to-Snap)
const startDrag = (e) => {
    if (e.button !== 0) return; // Solo clic izquierdo
    e.preventDefault();
    isDragging.value = true;
    draggedPosition.value = navPosition.value;
    
    window.addEventListener('mousemove', onDrag);
    window.addEventListener('mouseup', endDrag);
};

const onDrag = (e) => {
    if (!isDragging.value) return;
    
    const w = window.innerWidth;
    const h = window.innerHeight;
    
    const dTop = e.clientY;
    const dBottom = h - e.clientY;
    const dLeft = e.clientX;
    const dRight = w - e.clientX;
    
    const minDistance = Math.min(dTop, dBottom, dLeft, dRight);
    
    if (minDistance === dTop) {
        draggedPosition.value = 'top';
    } else if (minDistance === dBottom) {
        draggedPosition.value = 'bottom';
    } else if (minDistance === dLeft) {
        draggedPosition.value = 'left';
    } else if (minDistance === dRight) {
        draggedPosition.value = 'right';
    }
};

const endDrag = () => {
    if (isDragging.value) {
        navPosition.value = draggedPosition.value;
        localStorage.setItem('nav-position', draggedPosition.value);
        isDragging.value = false;
    }
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('mouseup', endDrag);
};

// Menú Contextual (Clic Derecho)
const openContextMenu = (e) => {
    e.preventDefault();
    contextMenu.value = {
        show: true,
        x: e.clientX,
        y: e.clientY
    };
};

const closeContextMenu = () => {
    contextMenu.value.show = false;
};

const setPosition = (position) => {
    navPosition.value = position;
    localStorage.setItem('nav-position', position);
    closeContextMenu();
};

onMounted(() => {
    window.addEventListener('click', closeContextMenu);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onDrag);
    window.removeEventListener('mouseup', endDrag);
    window.removeEventListener('click', closeContextMenu);
});

// --- ENLACE A NOTIFICACIONES GLOBALES DE ERRORES DE VALIDACIÓN ---
const showValidationToast = ref(false);
const validationErrors = ref({});

watch(() => page.props.errors, (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
        validationErrors.value = newErrors;
        showValidationToast.value = true;
        
        // Cierre automático tras 8 segundos
        setTimeout(() => {
            showValidationToast.value = false;
        }, 8000);
    } else {
        showValidationToast.value = false;
    }
}, { deep: true, immediate: true });
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <!-- Ghost Preview (Zona Fantasma para Arrastre) -->
        <div 
            v-if="isDragging"
            class="fixed z-50 pointer-events-none transition-all duration-150 ease-out border-2 border-dashed border-[var(--accent)] bg-[var(--accent)]/10 backdrop-blur-sm shadow-xl shadow-[var(--accent)]/5 rounded-[var(--border-radius)]"
            :class="{
                'top-0 left-0 w-full h-16': draggedPosition === 'top',
                'bottom-0 left-0 w-full h-16': draggedPosition === 'bottom',
                'top-0 left-0 w-64 h-full': draggedPosition === 'left',
                'top-0 right-0 w-64 h-full': draggedPosition === 'right'
            }"
        >
            <div class="w-full h-full flex items-center justify-center text-xs font-semibold tracking-wider uppercase text-[var(--accent)] animate-pulse">
                Soltar para ajustar aquí
            </div>
        </div>

        <!-- Menú Contextual Estilo Windows Taskbar -->
        <div 
            v-if="contextMenu.show" 
            class="fixed z-50 bg-[var(--bg-secondary)]/95 backdrop-blur border border-[var(--border-color)] rounded-lg shadow-2xl py-2 w-56 text-sm text-[var(--text-primary)] select-none animate-in fade-in zoom-in-95 duration-100"
            :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
            @click.stop
        >
            <div class="px-4 py-2 text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider border-b border-[var(--border-color)] flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[var(--accent)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
                Ubicación de la barra
            </div>
            <div class="py-1">
                <button 
                    @click="setPosition('top')"
                    class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center justify-between cursor-pointer"
                >
                    <span>Arriba</span>
                    <span v-if="navPosition === 'top'" class="text-[var(--accent)] font-bold">✓</span>
                </button>
                <button 
                    @click="setPosition('bottom')"
                    class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center justify-between cursor-pointer"
                >
                    <span>Abajo</span>
                    <span v-if="navPosition === 'bottom'" class="text-[var(--accent)] font-bold">✓</span>
                </button>
                <button 
                    @click="setPosition('left')"
                    class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center justify-between cursor-pointer"
                >
                    <span>Izquierda</span>
                    <span v-if="navPosition === 'left'" class="text-[var(--accent)] font-bold">✓</span>
                </button>
                <button 
                    @click="setPosition('right')"
                    class="w-full text-left px-4 py-2 hover:bg-white/10 transition flex items-center justify-between cursor-pointer"
                >
                    <span>Derecha</span>
                    <span v-if="navPosition === 'right'" class="text-[var(--accent)] font-bold">✓</span>
                </button>
            </div>
        </div>

        <!-- Contenedor Principal de la App con Luces -->
        <div class="min-h-screen bg-[var(--bg-primary)] text-[var(--text-primary)] relative overflow-hidden flex flex-col justify-between transition-colors duration-300">
            <!-- Luces de fondo premium -->
            <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-[var(--glow-1)] blur-[120px] pointer-events-none transition-colors duration-300"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-[var(--glow-2)] blur-[150px] pointer-events-none transition-colors duration-300"></div>

            <!-- Layout Principal -->
            <div class="flex-1 flex relative z-10">
                
                <!-- Barra de Navegación (Fixed e Interactiva, siempre con el usuario) -->
                <nav :class="navClasses" @contextmenu="openContextMenu">
                    
                    <!-- DISTRIBUCIÓN HORIZONTAL (Top/Bottom o para Móvil, sin scrollbars y compactada al margen) -->
                    <div class="w-full" :class="{ 'md:hidden': isVertical }">
                        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div class="flex h-16 justify-between items-center">
                                <!-- Logo + Enlaces de Navegación (Estilizado de forma compacta y sin scrollbars visibles) -->
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <!-- Manija de Arrastre Horizontal -->
                                    <div 
                                        @mousedown="startDrag"
                                        class="hidden md:flex items-center justify-center cursor-grab active:cursor-grabbing p-1.5 hover:bg-white/5 rounded text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition shrink-0"
                                        title="Arrastra para mover la barra de navegación"
                                    >
                                        <svg class="w-4 h-4 opacity-70" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8.5 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm7-15a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                        </svg>
                                    </div>

                                    <Link :href="route('dashboard')" class="flex items-center gap-2.5 shrink-0">
                                        <ApplicationMark class="block h-8 w-auto text-[var(--accent)]" />
                                        <span class="text-base font-bold tracking-wider text-[var(--text-primary)]">Licor Vintage</span>
                                    </Link>

                                    <!-- Listado de Enlaces Compactados con scrollbar oculto -->
                                    <div class="hidden items-center gap-1 md:flex overflow-x-auto py-1 pr-2 scrollbar-none">
                                        <Link
                                            v-for="item in navigation"
                                            :key="item.routeName"
                                            :href="route(item.routeName)"
                                            class="rounded-md px-2 py-1.5 text-xs font-semibold text-[var(--text-secondary)] transition hover:bg-white/10 hover:text-[var(--text-primary)] shrink-0"
                                            :class="{ 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]': isNavActive(item.routeName) }"
                                        >
                                            {{ item.label }}
                                        </Link>
                                    </div>
                                </div>

                                <!-- Buscador Global Compacto e Inteligente (Inline que se expande) -->
                                <div class="hidden md:flex items-center mx-4 shrink-0">
                                    <GlobalSearch :is-sidebar="false" />
                                </div>

                                <!-- Perfil + Salir (Alineado perfectamente al borde del margen max-w-7xl) -->
                                <div class="hidden items-center gap-3.5 md:flex shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-[var(--text-primary)]">{{ $page.props.auth.user?.name }}</div>
                                        <div class="text-[10px] text-[var(--accent)] font-medium">{{ roles.join(', ') }}</div>
                                    </div>
                                    <button class="rounded-md bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 hover:border-rose-300 dark:hover:border-rose-700/50 px-2.5 py-1.5 text-xs font-semibold hover:bg-rose-100 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 cursor-pointer transition shrink-0" @click="logout">
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

                        <!-- Dropdown de Menú Móvil -->
                        <div v-if="showingNavigationDropdown" class="border-t border-[var(--border-color)] px-4 py-3 md:hidden bg-[var(--bg-secondary)]">
                            <div class="mb-4">
                                <GlobalSearch :is-sidebar="true" />
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
                    </div>

                    <!-- DISTRIBUCIÓN VERTICAL (Solo Escritorio en Izquierda/Derecha, optimizada sin desbordamientos) -->
                    <div class="hidden md:flex flex-col h-full justify-between w-full min-h-0" v-if="isVertical">
                        <!-- Parte Superior: Logo, Buscador y Enlaces (Sección con scroll independiente si supera el alto) -->
                        <div class="flex flex-col min-h-0 flex-1">
                            <Link :href="route('dashboard')" class="flex items-center gap-3 py-2 justify-center shrink-0">
                                <ApplicationMark class="block h-10 w-auto text-[var(--accent)]" />
                                <span class="text-xl font-bold tracking-wider text-[var(--text-primary)]">Licor Vintage</span>
                            </Link>
                            <div class="h-px bg-[var(--border-color)] my-4 shrink-0"></div>
                            
                            <!-- Buscador en Sidebar (Inline) -->
                            <div class="my-3 px-1 shrink-0">
                                <GlobalSearch :is-sidebar="true" />
                            </div>
                            
                            <!-- Menú de Enlaces Verticales con Scroll Interno e independiente -->
                            <div class="flex-1 overflow-y-auto pr-1 space-y-1.5 min-h-0 scrollbar-thin">
                                <Link
                                    v-for="item in navigation"
                                    :key="item.routeName"
                                    :href="route(item.routeName)"
                                    class="flex items-center rounded-md px-3.5 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition hover:bg-white/10 hover:text-[var(--text-primary)] gap-3"
                                    :class="{ 'bg-[var(--accent)] text-white hover:bg-[var(--accent-hover)]': isNavActive(item.routeName) }"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-60"></span>
                                    <span>{{ item.label }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Parte Inferior: Manija de Arrastre, Perfil y Salir (Fijo abajo y sin flechas) -->
                        <div class="flex flex-col gap-3 pt-4 border-t border-[var(--border-color)] shrink-0">
                            <!-- Manija de Arrastre Vertical -->
                            <div 
                                @mousedown="startDrag"
                                class="flex items-center justify-center gap-2 cursor-grab active:cursor-grabbing py-2.5 px-3 bg-black/15 rounded border border-[var(--border-color)] text-xs text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-black/25 transition select-none"
                                title="Arrastra para mover la barra de navegación"
                            >
                                <svg class="w-4 h-4 opacity-70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.5 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm7-15a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 7.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                </svg>
                                <span>Arrastrar para mover</span>
                            </div>

                            <!-- Perfil de Usuario -->
                            <div class="bg-black/15 p-3 rounded-md border border-[var(--border-color)] flex flex-col gap-0.5">
                                <div class="text-sm font-semibold text-[var(--text-primary)] truncate">{{ $page.props.auth.user?.name }}</div>
                                <div class="text-xs text-[var(--accent)] truncate">{{ roles.join(', ') }}</div>
                            </div>

                            <button class="w-full rounded-md bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 hover:border-rose-300 dark:hover:border-rose-700/50 py-2.5 text-sm hover:bg-rose-100 dark:hover:bg-rose-900/30 text-rose-700 dark:text-rose-400 hover:text-rose-800 dark:hover:text-rose-300 cursor-pointer transition" @click="logout">
                                Salir
                            </button>
                        </div>
                    </div>

                </nav>

                <!-- Contenido Restante (Con paddings dinámicos coordinados) -->
                <div :class="contentClasses">
                    <div>
                         <!-- Mensajes Flash -->
                         <div v-if="flashMessage.show" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8 w-full">
                             <div
                                 class="rounded-md border px-4 py-3 text-sm shadow-sm flex items-center justify-between gap-2 transition-all duration-300"
                                 :class="flashMessage.type === 'success' ? 'border-emerald-200/20 bg-emerald-950/40 text-emerald-300' : 'border-red-200/20 bg-red-950/40 text-red-300'"
                             >
                                 <span>{{ flashMessage.text }}</span>
                                 <button type="button" @click="flashMessage.show = false" class="text-current opacity-70 hover:opacity-100 transition focus:outline-none">
                                     <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                     </svg>
                                 </button>
                             </div>
                         </div>

                        <!-- Encabezado de Página -->
                        <header v-if="$slots.header" class="border-b border-[var(--border-color)] bg-[var(--bg-tertiary)]/30 backdrop-blur-md transition-colors duration-300">
                            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                <slot name="header" />
                            </div>
                        </header>

                        <!-- Contenido Principal (Alineado con max-w-7xl) -->
                        <main class="py-8 flex-1">
                            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                                <slot />
                            </div>
                        </main>
                    </div>

                    <!-- Pie de Página Premium con Contador de Visitas -->
                    <footer class="border-t border-[var(--border-color)] bg-[var(--bg-secondary)]/50 backdrop-blur-md py-6 text-center text-xs text-[var(--text-secondary)] transition-colors duration-300 mt-auto">
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
        </div>
        <!-- Toast de Errores de Validación -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="showValidationToast && Object.keys(validationErrors).length > 0"
                class="fixed top-20 right-4 z-50 max-w-md w-full bg-stone-950/95 backdrop-blur-md border border-rose-500/40 rounded-lg shadow-[0_0_20px_rgba(244,63,94,0.15)] overflow-hidden pointer-events-auto flex flex-col"
            >
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-bold text-white">
                                Error en la operación
                            </p>
                            <p class="mt-1 text-xs text-stone-300">
                                Por favor, corrige los siguientes errores:
                            </p>
                            <ul class="mt-2 space-y-1 text-xs text-rose-300 list-disc list-inside">
                                <li v-for="(error, field) in validationErrors" :key="field">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button 
                                type="button" 
                                class="inline-flex rounded-md bg-stone-900 text-stone-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 cursor-pointer transition p-1 border border-stone-800"
                                @click="showValidationToast = false"
                            >
                                <span class="sr-only">Cerrar</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Widget de Apariencia y Accesibilidad -->
        <AppearanceWidget />
    </div>
</template>

<style scoped>
/* Estilo sutil para scrollbars delgados en el menú vertical */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 2px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: var(--accent);
}

/* Ocultar barra de scroll para el menú horizontal */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;  /* IE y Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>

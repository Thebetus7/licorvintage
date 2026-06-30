<script setup>
import { ref, onMounted, watch, computed } from 'vue';

const isOpen = ref(false);

const theme = ref('adults'); // adults, youth, kids
const mode = ref('dark'); // dark, light, auto
const fontScale = ref(1.0); // 1.0, 1.15, 1.30, 1.50
const highContrast = ref(false);

// Cargar preferencias guardadas
onMounted(() => {
    theme.value = localStorage.getItem('appearance-theme') || 'adults';
    mode.value = localStorage.getItem('appearance-mode') || 'dark';
    fontScale.value = parseFloat(localStorage.getItem('appearance-font-scale') || '1.0');
    highContrast.value = localStorage.getItem('appearance-contrast') === 'true';

    applyStyles();

    // Si está en automático, verificar periódicamente (por si cambia la hora del sistema)
    if (mode.value === 'auto') {
        const interval = setInterval(() => {
            if (mode.value === 'auto') {
                applyStyles();
            } else {
                clearInterval(interval);
            }
        }, 60000);
    }
});

// Guardar y aplicar estilos ante cambios en el estado reactivo
watch([theme, mode, fontScale, highContrast], () => {
    localStorage.setItem('appearance-theme', theme.value);
    localStorage.setItem('appearance-mode', mode.value);
    localStorage.setItem('appearance-font-scale', fontScale.value.toString());
    localStorage.setItem('appearance-contrast', highContrast.value.toString());
    applyStyles();
});

const applyStyles = () => {
    const el = document.documentElement;

    // 1. Limpiar clases previas de tema
    el.classList.remove('theme-adults', 'theme-youth', 'theme-kids');
    el.classList.add(`theme-${theme.value}`);

    // 2. Determinar si usar modo claro u oscuro
    let isLight = false;
    if (mode.value === 'light') {
        isLight = true;
    } else if (mode.value === 'auto') {
        const hours = new Date().getHours();
        // Si está entre las 6 AM y las 7 PM (19:00), es día (light)
        isLight = hours >= 6 && hours < 19;
    }

    if (isLight) {
        el.classList.add('theme-light');
    } else {
        el.classList.remove('theme-light');
    }

    // 3. Alto Contraste
    if (highContrast.value) {
        el.classList.add('contrast-high');
    } else {
        el.classList.remove('contrast-high');
    }

    // 4. Escala de Fuente
    el.style.setProperty('--font-scale', fontScale.value.toString());
};

const increaseFont = () => {
    if (fontScale.value < 1.5) {
        fontScale.value = parseFloat((fontScale.value + 0.15).toFixed(2));
    }
};

const decreaseFont = () => {
    if (fontScale.value > 0.85) {
        fontScale.value = parseFloat((fontScale.value - 0.15).toFixed(2));
    }
};

const resetFont = () => {
    fontScale.value = 1.0;
};

// --- LÓGICA DE ARRASTRE Y SNAP A ESQUINAS ---
const activeCorner = ref(localStorage.getItem('appearance-widget-corner') || 'bottom-right');
const isDragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
let startPointer = { x: 0, y: 0 };

const onPointerDown = (event) => {
    isDragging.value = true;
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;
    
    startPointer = { x: clientX, y: clientY };
    dragOffset.value = { x: 0, y: 0 };
    
    if (event.touches) {
        window.addEventListener('touchmove', onPointerMove, { passive: false });
        window.addEventListener('touchend', onPointerUp);
    } else {
        window.addEventListener('mousemove', onPointerMove);
        window.addEventListener('mouseup', onPointerUp);
    }
};

const onPointerMove = (event) => {
    if (!isDragging.value) return;
    
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;
    
    dragOffset.value = {
        x: clientX - startPointer.x,
        y: clientY - startPointer.y
    };
    
    if (event.cancelable) {
        event.preventDefault();
    }
};

const onPointerUp = () => {
    if (!isDragging.value) return;
    isDragging.value = false;
    
    window.removeEventListener('mousemove', onPointerMove);
    window.removeEventListener('mouseup', onPointerUp);
    window.removeEventListener('touchmove', onPointerMove);
    window.removeEventListener('touchend', onPointerUp);
    
    const dist = Math.sqrt(dragOffset.value.x * dragOffset.value.x + dragOffset.value.y * dragOffset.value.y);
    
    if (dist < 6) {
        // Clic simple: abrir/cerrar panel
        isOpen.value = !isOpen.value;
        dragOffset.value = { x: 0, y: 0 };
    } else {
        // Arrastre: calcular esquina más cercana
        const w = window.innerWidth;
        const h = window.innerHeight;
        let x0 = 0;
        let y0 = 0;
        
        // Posiciones base de las esquinas en px (centro del botón de 56px de ancho)
        if (activeCorner.value === 'top-left') {
            x0 = 24 + 28;
            y0 = 80 + 28;
        } else if (activeCorner.value === 'top-right') {
            x0 = w - 24 - 28;
            y0 = 80 + 28;
        } else if (activeCorner.value === 'bottom-left') {
            x0 = 24 + 28;
            y0 = h - 24 - 28;
        } else { // bottom-right
            x0 = w - 24 - 28;
            y0 = h - 24 - 28;
        }
        
        // Coordenadas finales del centro tras el arrastre
        const xf = x0 + dragOffset.value.x;
        const yf = y0 + dragOffset.value.y;
        
        // Distancias a las 4 esquinas del viewport
        const corners = [
            { name: 'top-left', x: 52, y: 108 },
            { name: 'top-right', x: w - 52, y: 108 },
            { name: 'bottom-left', x: 52, y: h - 52 },
            { name: 'bottom-right', x: w - 52, y: h - 52 }
        ];
        
        let minCorner = 'bottom-right';
        let minDist = Infinity;
        
        corners.forEach(c => {
            const dx = xf - c.x;
            const dy = yf - c.y;
            const d = dx * dx + dy * dy;
            if (d < minDist) {
                minDist = d;
                minCorner = c.name;
            }
        });
        
        activeCorner.value = minCorner;
        localStorage.setItem('appearance-widget-corner', minCorner);
        dragOffset.value = { x: 0, y: 0 };
    }
};

const panelClasses = computed(() => {
    if (activeCorner.value === 'bottom-right') return 'bottom-16 right-0';
    if (activeCorner.value === 'bottom-left') return 'bottom-16 left-0';
    if (activeCorner.value === 'top-right') return 'top-16 right-0';
    if (activeCorner.value === 'top-left') return 'top-16 left-0';
    return 'bottom-16 right-0';
});
</script>

<template>
    <div
        class="fixed z-50 select-none"
        :class="[
            activeCorner === 'top-left' ? 'top-20 left-6' : '',
            activeCorner === 'top-right' ? 'top-20 right-6' : '',
            activeCorner === 'bottom-left' ? 'bottom-6 left-6' : '',
            activeCorner === 'bottom-right' ? 'bottom-6 right-6' : ''
        ]"
        :style="{
            transform: isDragging ? `translate3d(${dragOffset.x}px, ${dragOffset.y}px, 0)` : 'none',
            transition: isDragging ? 'none' : 'all 0.35s cubic-bezier(0.18, 0.89, 0.32, 1.28)'
        }"
    >
        <!-- Botón flotante -->
        <button
            @mousedown="onPointerDown"
            @touchstart="onPointerDown"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--accent)] text-white shadow-xl hover:scale-105 hover:bg-[var(--accent-hover)] transition-all duration-300 border border-[var(--border-color)] cursor-grab active:cursor-grabbing"
            aria-label="Controles de accesibilidad y temas"
            title="Accesibilidad y Apariencia"
        >
            <!-- Icono Accesibilidad -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 pointer-events-none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.05 4.575a1 1 0 0 1 .8-.4h2.3a1 1 0 0 1 .8.4l1.3 2.147a1 1 0 0 0 .733.456l2.454.368a1 1 0 0 1 .535 1.646l-1.84 1.718a1 1 0 0 0-.294.902l.502 2.44a1 1 0 0 1-1.487 1.08l-2.146-1.196a1 1 0 0 0-.964 0l-2.146 1.196a1 1 0 0 1-1.487-1.08l.502-2.44a1 1 0 0 0-.294-.902l-1.84-1.718a1 1 0 0 1 .535-1.646l2.454-.368a1 1 0 0 0 .733-.456l1.3-2.147Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
            </svg>
        </button>

        <!-- Panel de configuración visual (Totalmente temático y adaptativo) -->
        <div
            v-if="isOpen"
            class="absolute w-80 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-secondary)] p-5 text-[var(--text-primary)] shadow-2xl backdrop-blur-md transition-all duration-300 appearance-panel"
            :class="panelClasses"
        >
            <div class="flex items-center justify-between border-b border-[var(--border-color)] pb-3 mb-4">
                <h3 class="text-base font-bold tracking-wide flex items-center gap-2 text-[var(--text-primary)]">
                    Accesibilidad y Temas
                </h3>
                <button @click="isOpen = false" class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] text-sm cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- 1. Selección de Temas -->
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)] mb-2">
                    Seleccionar Tema
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        @click="theme = 'adults'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="theme === 'adults' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Adultos
                    </button>
                    <button
                        @click="theme = 'youth'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="theme === 'youth' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Jóvenes
                    </button>
                    <button
                        @click="theme = 'kids'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="theme === 'kids' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Niños
                    </button>
                </div>
            </div>

            <!-- 2. Selección de Modo Día/Noche -->
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)] mb-2">
                    Modo Día / Noche
                </label>
                <div class="grid grid-cols-3 gap-2">
                    <button
                        @click="mode = 'light'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="mode === 'light' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Día
                    </button>
                    <button
                        @click="mode = 'dark'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="mode === 'dark' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                    >
                        Noche
                    </button>
                    <button
                        @click="mode = 'auto'"
                        class="px-2 py-2 text-xs font-medium rounded-lg border transition cursor-pointer"
                        :class="mode === 'auto' ? 'bg-[var(--accent)] text-[var(--bg-primary)] border-[var(--accent)] font-bold' : 'border-[var(--border-color)] bg-[var(--bg-primary)]/50 hover:bg-[var(--bg-primary)] text-[var(--text-secondary)] hover:text-[var(--text-primary)]'"
                        title="Modifica automáticamente según tu horario local"
                    >
                        Auto
                    </button>
                </div>
            </div>

            <!-- 3. Tamaño del Texto -->
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)] mb-2 flex justify-between items-center">
                    <span>Tamaño de Letra</span>
                    <span class="text-[var(--accent)] text-xs font-mono font-bold">{{ Math.round(fontScale * 100) }}%</span>
                </label>
                <div class="flex items-center gap-2">
                    <button
                        @click="decreaseFont"
                        class="flex-1 px-3 py-2 bg-[var(--bg-primary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-primary)] text-sm font-bold rounded-lg cursor-pointer text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition"
                        title="Disminuir texto"
                    >
                        A -
                    </button>
                    <button
                        @click="resetFont"
                        class="px-3 py-2 bg-[var(--bg-primary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-primary)] text-xs font-medium rounded-lg cursor-pointer text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition"
                        title="Restablecer al 100%"
                    >
                        Normal
                    </button>
                    <button
                        @click="increaseFont"
                        class="flex-1 px-3 py-2 bg-[var(--bg-primary)]/50 border border-[var(--border-color)] hover:bg-[var(--bg-primary)] text-sm font-bold rounded-lg cursor-pointer text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition"
                        title="Aumentar texto"
                    >
                        A +
                    </button>
                </div>
            </div>

            <!-- 4. Alto Contraste -->
            <div class="flex items-center justify-between pt-3 border-t border-[var(--border-color)]">
                <span class="text-xs font-semibold uppercase tracking-wider text-[var(--text-secondary)]">Alto Contraste</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="highContrast"
                        class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-[var(--bg-primary)] border border-[var(--border-color)] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-stone-400 dark:after:bg-stone-500 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[var(--accent)] peer-checked:after:bg-[var(--bg-primary)]"></div>
                </label>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';

// --- Customizer State ---
const isOpen = ref(false);

const sidebarPosition = ref('left'); // 'left', 'right', 'top', 'bottom'
const sidebarLocked = ref(true);
const theme = ref('adultos'); // 'adultos', 'jovenes', 'ninos'
const fontFamily = ref('Inter'); // 'Inter', 'Space Grotesk', 'Fredoka', 'Outfit', 'Roboto'
const fontSize = ref('mediano'); // 'pequeno', 'mediano', 'grande'
const highContrast = ref(false);
const dayNightMode = ref('dia'); // 'dia', 'noche', 'auto'

// --- Draggable Floating Button State ---
const posX = ref(null);
const posY = ref(null);
const isDraggingButton = ref(false);
let startDragX = 0;
let startDragY = 0;
let startPosX = 0;
let startPosY = 0;
const dragThreshold = 6;
let dragDistance = 0;

// Computed style for the floating button wrapper
const buttonStyle = computed(() => {
    if (posX.value === null || posY.value === null) {
        return {
            bottom: '24px',
            right: '24px'
        };
    }
    return {
        left: `${posX.value}px`,
        top: `${posY.value}px`,
        bottom: 'auto',
        right: 'auto'
    };
});

// Computed style to open the popover panel in the correct direction based on screen quadrant
const panelStyle = computed(() => {
    const w = window.innerWidth;
    const h = window.innerHeight;
    
    // Default position if not dragged
    if (posX.value === null || posY.value === null) {
        return {
            bottom: '64px',
            right: '0px'
        };
    }
    
    const btnWidth = 56;
    const btnHeight = 56;
    const midX = w / 2;
    const midY = h / 2;
    
    const style = {};
    
    // Horizontal alignment
    if (posX.value + btnWidth / 2 < midX) {
        // Button on the left half -> open panel to the right
        style.left = '0px';
    } else {
        // Button on the right half -> open panel to the left
        style.right = '0px';
    }
    
    // Vertical alignment
    if (posY.value + btnHeight / 2 < midY) {
        // Button on the top half -> open panel downwards
        style.top = '64px';
    } else {
        // Button on the bottom half -> open panel upwards
        style.bottom = '64px';
    }
    
    return style;
});

// Load all preferences
onMounted(() => {
    const savedPos = localStorage.getItem('sidebar-position');
    if (savedPos) sidebarPosition.value = savedPos;

    const savedLock = localStorage.getItem('sidebar-locked');
    if (savedLock !== null) sidebarLocked.value = savedLock === 'true';

    const savedTheme = localStorage.getItem('pref-theme');
    if (savedTheme) theme.value = savedTheme;

    const savedFontFamily = localStorage.getItem('pref-font-family');
    if (savedFontFamily) fontFamily.value = savedFontFamily;

    const savedFontSize = localStorage.getItem('pref-font-size');
    if (savedFontSize) fontSize.value = savedFontSize;

    const savedContrast = localStorage.getItem('pref-contrast');
    if (savedContrast !== null) highContrast.value = savedContrast === 'true';

    const savedDayNightMode = localStorage.getItem('pref-daynight-mode');
    if (savedDayNightMode) {
        dayNightMode.value = savedDayNightMode;
    } else {
        // Fallback/migration check from old boolean
        const savedAutoDayNight = localStorage.getItem('pref-auto-daynight');
        if (savedAutoDayNight !== null) {
            dayNightMode.value = savedAutoDayNight === 'true' ? 'auto' : 'dia';
        }
    }

    // Load floating button coordinates
    const savedBtnX = localStorage.getItem('customizer-btn-x');
    const savedBtnY = localStorage.getItem('customizer-btn-y');
    if (savedBtnX !== null && savedBtnY !== null) {
        posX.value = parseFloat(savedBtnX);
        posY.value = parseFloat(savedBtnY);
    }

    applyPreferences();
    
    // Listen for changes from other components (like AppLayout itself)
    window.addEventListener('preference-updated', (e) => {
        const { key, value } = e.detail;
        if (key === 'sidebar-position') sidebarPosition.value = value;
        if (key === 'sidebar-locked') sidebarLocked.value = value;
        if (key === 'pref-theme') theme.value = value;
        if (key === 'pref-font-size') fontSize.value = value;
        if (key === 'pref-contrast') highContrast.value = value;
        if (key === 'pref-daynight-mode') dayNightMode.value = value;
    });
});

// Watch state and save/dispatch updates
watch([sidebarPosition, sidebarLocked, theme, fontFamily, fontSize, highContrast, dayNightMode], () => {
    localStorage.setItem('sidebar-position', sidebarPosition.value);
    localStorage.setItem('sidebar-locked', sidebarLocked.value ? 'true' : 'false');
    localStorage.setItem('pref-theme', theme.value);
    localStorage.setItem('pref-font-family', fontFamily.value);
    localStorage.setItem('pref-font-size', fontSize.value);
    localStorage.setItem('pref-contrast', highContrast.value ? 'true' : 'false');
    localStorage.setItem('pref-daynight-mode', dayNightMode.value);
    
    applyPreferences();

    // Broadcast change to other active components (like AppLayout.vue sidebar)
    window.dispatchEvent(new CustomEvent('preference-updated-from-customizer', {
        detail: {
            sidebarPosition: sidebarPosition.value,
            sidebarLocked: sidebarLocked.value,
            theme: theme.value,
            fontSize: fontSize.value,
            highContrast: highContrast.value,
            dayNightMode: dayNightMode.value
        }
    }));
});

// Apply classes & styles to document
const applyPreferences = () => {
    const root = document.documentElement;
    root.className = ''; // reset classes
    
    // Theme class
    root.classList.add(`theme-${theme.value}`);
    
    // Font size class
    root.classList.add(`font-size-${fontSize.value}`);
    
    // High contrast class
    if (highContrast.value) {
        root.classList.add('high-contrast');
    }

    // Font Family inline style to body to avoid layout distortion
    document.body.style.fontFamily = `"${fontFamily.value}", sans-serif`;

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

// --- DRAG AND DROP FLOATING BUTTON MECHANICS ---
const onButtonMousedown = (e) => {
    if (e.button !== 0) return; // only left click
    isDraggingButton.value = false;
    dragDistance = 0;

    const rect = e.currentTarget.getBoundingClientRect();
    startPosX = rect.left;
    startPosY = rect.top;
    startDragX = e.clientX;
    startDragY = e.clientY;

    document.addEventListener('mousemove', onButtonMousemove);
    document.addEventListener('mouseup', onButtonMouseup);
};

const onButtonMousemove = (e) => {
    const dx = e.clientX - startDragX;
    const dy = e.clientY - startDragY;
    dragDistance = Math.sqrt(dx * dx + dy * dy);

    if (dragDistance > dragThreshold) {
        isDraggingButton.value = true;

        let newX = startPosX + dx;
        let newY = startPosY + dy;

        const btnWidth = 56;
        const btnHeight = 56;
        const pad = 16;

        const minX = pad;
        const maxX = window.innerWidth - btnWidth - pad;
        const minY = pad;
        const maxY = window.innerHeight - btnHeight - pad;

        newX = Math.max(minX, Math.min(newX, maxX));
        newY = Math.max(minY, Math.min(newY, maxY));

        posX.value = newX;
        posY.value = newY;
    }
};

const onButtonMouseup = (e) => {
    document.removeEventListener('mousemove', onButtonMousemove);
    document.removeEventListener('mouseup', onButtonMouseup);

    if (isDraggingButton.value) {
        snapToScreenEdge();
        localStorage.setItem('customizer-btn-x', posX.value);
        localStorage.setItem('customizer-btn-y', posY.value);
        
        e.preventDefault();
        e.stopPropagation();
    }
};

// Touch support for mobile devices
const onButtonTouchstart = (e) => {
    if (e.touches.length !== 1) return;
    isDraggingButton.value = false;
    dragDistance = 0;

    const rect = e.currentTarget.getBoundingClientRect();
    startPosX = rect.left;
    startPosY = rect.top;
    startDragX = e.touches[0].clientX;
    startDragY = e.touches[0].clientY;

    document.addEventListener('touchmove', onButtonTouchmove, { passive: false });
    document.addEventListener('touchend', onButtonTouchend);
};

const onButtonTouchmove = (e) => {
    if (e.touches.length !== 1) return;
    const dx = e.touches[0].clientX - startDragX;
    const dy = e.touches[0].clientY - startDragY;
    dragDistance = Math.sqrt(dx * dx + dy * dy);

    if (dragDistance > dragThreshold) {
        isDraggingButton.value = true;
        e.preventDefault(); // prevent scrolling

        let newX = startPosX + dx;
        let newY = startPosY + dy;

        const btnWidth = 56;
        const btnHeight = 56;
        const pad = 16;

        const minX = pad;
        const maxX = window.innerWidth - btnWidth - pad;
        const minY = pad;
        const maxY = window.innerHeight - btnHeight - pad;

        newX = Math.max(minX, Math.min(newX, maxX));
        newY = Math.max(minY, Math.min(newY, maxY));

        posX.value = newX;
        posY.value = newY;
    }
};

const onButtonTouchend = (e) => {
    document.removeEventListener('touchmove', onButtonTouchmove);
    document.removeEventListener('touchend', onButtonTouchend);

    if (isDraggingButton.value) {
        snapToScreenEdge();
        localStorage.setItem('customizer-btn-x', posX.value);
        localStorage.setItem('customizer-btn-y', posY.value);
    }
};

const snapToScreenEdge = () => {
    const w = window.innerWidth;
    const h = window.innerHeight;
    const btnWidth = 56;
    const btnHeight = 56;
    const pad = 24; // Margen elegante desde las esquinas

    const currentX = posX.value;
    const currentY = posY.value;

    // Definición de las 4 esquinas de la pantalla
    const corners = [
        { x: pad,                  y: pad },                  // Superior Izquierda
        { x: w - btnWidth - pad,   y: pad },                  // Superior Derecha
        { x: pad,                  y: h - btnHeight - pad },  // Inferior Izquierda
        { x: w - btnWidth - pad,   y: h - btnHeight - pad }   // Inferior Derecha
    ];

    // Buscar la esquina más cercana mediante distancia euclidiana
    let closestCorner = corners[0];
    let minDistance = Infinity;

    corners.forEach(corner => {
        const dx = currentX - corner.x;
        const dy = currentY - corner.y;
        const dist = dx * dx + dy * dy; // distancia al cuadrado es suficiente
        if (dist < minDistance) {
            minDistance = dist;
            closestCorner = corner;
        }
    });

    // Acoplar a la esquina más cercana
    posX.value = closestCorner.x;
    posY.value = closestCorner.y;
};

const toggleOpen = (e) => {
    // If user dragged, do not toggle the panel!
    if (dragDistance > dragThreshold) {
        e.preventDefault();
        e.stopPropagation();
        return;
    }
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div 
        class="fixed z-50 select-none font-sans transition-all duration-100"
        :style="buttonStyle"
    >
        <!-- Floating Round Button -->
        <button 
            @mousedown="onButtonMousedown"
            @touchstart="onButtonTouchstart"
            @click="toggleOpen"
            class="w-14 h-14 bg-[#0f172a] border border-slate-700 text-white rounded-full flex items-center justify-center shadow-2xl hover:bg-slate-800 hover:scale-105 active:scale-95 transition-transform duration-200 group cursor-grab active:cursor-grabbing"
            title="Personalizar diseño y accesibilidad (Arrastra para mover)"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-amber-400 group-hover:rotate-45 transition-transform duration-300 pointer-events-none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122l9.53-9.53m-9.53 9.53a2.25 2.25 0 103.181-3.182m-3.18 3.185a1.5 1.5 0 002.122-2.121m-2.122 2.121L4 20h3.878l9.53-9.53m-9.53 9.53l9.53-9.53M9.53 16.122a3 3 0 11-4.243-4.243m1.105 4.243L8.25 12.75" />
            </svg>
        </button>

        <!-- Popover Panel -->
        <div 
            v-if="isOpen"
            class="absolute w-80 bg-[#0f172a] text-white border border-slate-800 rounded-2xl p-5 shadow-2xl space-y-4 animate-pop-in"
            :style="panelStyle"
        >
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122l9.53-9.53m-9.53 9.53a2.25 2.25 0 103.181-3.182m-3.18 3.185a1.5 1.5 0 002.122-2.121m-2.122 2.121L4 20h3.878l9.53-9.53m-9.53 9.53l9.53-9.53M9.53 16.122a3 3 0 11-4.243-4.243m1.105 4.243L8.25 12.75" />
                    </svg>
                    <span class="font-black text-sm uppercase tracking-wider text-slate-100">Diseño y Accesibilidad</span>
                </div>
                <button @click="isOpen = false" class="text-slate-400 hover:text-white transition p-1" aria-label="Cerrar">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Theme Selector -->
            <div class="space-y-1.5">
                <label class="block text-[10px] uppercase font-bold text-slate-400">Temas del Sitio</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button 
                        @click="theme = 'ninos'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="theme === 'ninos' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Niños</span>
                        <span class="text-[8px] opacity-75">Celeste/Blanco</span>
                    </button>
                    <button 
                        @click="theme = 'jovenes'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="theme === 'jovenes' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 text-yellow-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Jóvenes</span>
                        <span class="text-[8px] opacity-75">Marino/Celeste</span>
                    </button>
                    <button 
                        @click="theme = 'adultos'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="theme === 'adultos' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Adultos</span>
                        <span class="text-[8px] opacity-75">Marino/Gris</span>
                    </button>
                </div>
            </div>

            <!-- Typography Selector -->
            <div class="space-y-1.5">
                <label class="block text-[10px] uppercase font-bold text-slate-400">Tipografía (Fuentes)</label>
                <div class="grid grid-cols-2 gap-1">
                    <button 
                        v-for="font in ['Inter', 'Space Grotesk', 'Fredoka', 'Outfit', 'Roboto']" 
                        :key="font"
                        @click="fontFamily = font"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition text-left"
                        :class="fontFamily === font ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                        :style="{ fontFamily: font }"
                    >
                        {{ font }}
                    </button>
                </div>
            </div>

            <!-- Font Size -->
            <div class="space-y-1.5">
                <label class="block text-[10px] uppercase font-bold text-slate-400">Tamaño del Texto</label>
                <div class="grid grid-cols-3 gap-1">
                    <button 
                        @click="fontSize = 'pequeno'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition"
                        :class="fontSize === 'pequeno' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        Pequeño
                    </button>
                    <button 
                        @click="fontSize = 'mediano'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition"
                        :class="fontSize === 'mediano' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        Mediano
                    </button>
                    <button 
                        @click="fontSize = 'grande'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition"
                        :class="fontSize === 'grande' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        Grande
                    </button>
                </div>
            </div>

            <!-- Sidebar controls (Moveable / Lockable) -->
            <div class="space-y-1.5 pt-2 border-t border-slate-800">
                <div class="flex items-center justify-between">
                    <label class="block text-[10px] uppercase font-bold text-slate-400">Control de la Barra</label>
                    <button 
                        @click="sidebarLocked = !sidebarLocked"
                        class="text-[10px] font-bold px-2 py-1 rounded transition flex items-center gap-1"
                        :class="sidebarLocked ? 'bg-emerald-600/25 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/25 text-amber-400 border border-amber-500/30'"
                    >
                        <svg v-if="sidebarLocked" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 10v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ sidebarLocked ? 'Bloqueada' : 'Móvil' }}</span>
                    </button>
                </div>
                <div class="grid grid-cols-4 gap-1">
                    <button 
                        v-for="pos in ['left', 'right', 'top', 'bottom']" 
                        :key="pos"
                        @click="sidebarPosition = pos"
                        class="px-1.5 py-1.5 rounded text-[9px] font-bold capitalize transition"
                        :class="sidebarPosition === pos ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        {{ pos }}
                    </button>
                </div>
            </div>

            <!-- Modo de Pantalla (Día / Noche / Auto) -->
            <div class="space-y-1.5 pt-2 border-t border-slate-800">
                <label class="block text-[10px] uppercase font-bold text-slate-400">Modo de Pantalla</label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button 
                        @click="dayNightMode = 'dia'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="dayNightMode === 'dia' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.364l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path>
                        </svg>
                        <span>Día</span>
                    </button>
                    <button 
                        @click="dayNightMode = 'noche'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="dayNightMode === 'noche' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <span>Noche</span>
                    </button>
                    <button 
                        @click="dayNightMode = 'auto'"
                        class="px-2 py-1.5 rounded-lg text-[10px] font-bold transition flex flex-col items-center justify-center gap-1"
                        :class="dayNightMode === 'auto' ? 'bg-amber-500 text-[#0f172a]' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                    >
                        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                        <span>Auto</span>
                    </button>
                </div>
            </div>

            <!-- Accessibility (Contraste) -->
            <div class="pt-2 border-t border-slate-800 text-xs text-slate-300">
                <label class="flex items-center gap-2 cursor-pointer p-1.5 rounded hover:bg-slate-800/40 transition">
                    <input type="checkbox" v-model="highContrast" class="rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-0">
                    <span>Alto Contraste</span>
                </label>
            </div>
        </div>
    </div>
</template>

<style>
/* Animation for popover */
@keyframes popIn {
    from { opacity: 0; transform: scale(0.9) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-pop-in {
    animation: popIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>


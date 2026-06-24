<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Customizer from '@/Components/Customizer.vue';

// Load and apply theme and accessibility settings on guest auth pages
onMounted(() => {
    const root = document.documentElement;
    const theme = localStorage.getItem('pref-theme') || 'adultos';
    const fontSize = localStorage.getItem('pref-font-size') || 'mediano';
    const contrast = localStorage.getItem('pref-contrast') === 'true';
    const dayNightMode = localStorage.getItem('pref-daynight-mode') || 'dia';
    const fontFamily = localStorage.getItem('pref-font-family') || 'Inter';

    // Reset and apply core classes
    root.className = '';
    root.classList.add(`theme-${theme}`);
    root.classList.add(`font-size-${fontSize}`);
    if (contrast) root.classList.add('high-contrast');
    
    // Apply font family inline to body
    document.body.style.fontFamily = `"${fontFamily}", sans-serif`;

    // Determine night-active mode
    let isNightActive = false;
    if (dayNightMode === 'noche') {
        isNightActive = true;
    } else if (dayNightMode === 'auto') {
        const hour = new Date().getHours();
        const isNight = hour >= 18 || hour < 6;
        if (isNight) {
            isNightActive = true;
        }
    }

    if (isNightActive) {
        root.classList.add('night-active');
    }

    // Determine dark mode class
    let isDark = false;
    if (contrast) {
        isDark = true;
    } else if (theme === 'jovenes') {
        isDark = true;
    } else if (isNightActive) {
        isDark = true;
    }

    if (isDark) {
        root.classList.add('dark');
    }
});
</script>

<template>
    <!-- Background adapts dynamically to var(--bg-main) through our customizer CSS overrides -->
    <div class="auth-container min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100 transition-colors duration-300 relative">
        <!-- Premium "Volver al Inicio" Button -->
        <Link :href="'/'" class="back-home-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver al Inicio</span>
        </Link>

        <div class="mt-8 sm:mt-0">
            <slot name="logo" />
        </div>

        <!-- Card container adapts dynamically to var(--bg-card), var(--border), and var(--radius) -->
        <div class="auth-card w-full sm:max-w-md mt-6 px-6 py-5 bg-white border border-slate-200 shadow-2xl overflow-hidden sm:rounded-lg transition-all duration-300">
            <slot />
        </div>

        <!-- Floating Dynamic Customizer -->
        <Customizer />
    </div>
</template>


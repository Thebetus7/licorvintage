<script setup>
import AppearanceWidget from '@/Components/AppearanceWidget.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[var(--bg-primary)] text-[var(--text-primary)] relative overflow-hidden transition-colors duration-300">
        <!-- Efectos de Luces traseras -->
        <div class="absolute top-[-20%] left-[-20%] w-[60%] h-[60%] rounded-full bg-[var(--glow-1)] blur-[120px] pointer-events-none transition-colors duration-300"></div>
        <div class="absolute bottom-[-20%] right-[-20%] w-[60%] h-[60%] rounded-full bg-[var(--glow-2)] blur-[150px] pointer-events-none transition-colors duration-300"></div>

        <div class="relative z-10">
            <slot name="logo" />
        </div>

        <div class="relative z-10 w-full sm:max-w-md mt-6 px-6 py-8 bg-[var(--bg-tertiary)]/90 backdrop-blur-md border border-[var(--border-color)] shadow-2xl overflow-hidden sm:rounded-xl transition-colors duration-300">
            <slot />
        </div>

        <!-- Widget de Accesibilidad y Temas -->
        <AppearanceWidget />
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'" class="flex flex-col items-center gap-2 group select-none">
        <!-- Contenedor premium circular del logo -->
        <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform duration-200 border" style="background-color: var(--bg-nav); border-color: var(--border);">
            <svg class="w-7 h-7" style="color: var(--accent);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a7 7 0 007-7V4H5v4a7 7 0 007 7zM12 15v6M9 21h6"></path>
            </svg>
        </div>
        <span class="text-lg font-black tracking-widest uppercase transition-colors duration-200" style="color: var(--accent);">Licor Vintage</span>
    </Link>
</template>


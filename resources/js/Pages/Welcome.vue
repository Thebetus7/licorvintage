<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Customizer from '@/Components/Customizer.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    productos: Array,
});

const searchQuery = ref('');

const filteredProductos = computed(() => {
    if (!searchQuery.value) return props.productos;
    const query = searchQuery.value.toLowerCase().trim();
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(query) || 
        (p.descripcion && p.descripcion.toLowerCase().includes(query))
    );
});

const handleSearchInput = () => {
    const catalogSection = document.getElementById('catalogo');
    if (catalogSection) {
        catalogSection.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <Head title="Licor Vintage - Inicio" />

    <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
        <!-- Header / Navbar -->
        <header class="bg-[#0f172a] text-white shadow-md">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-2xl font-black tracking-wider text-amber-400">LICOR VINTAGE</span>
                </div>
                
                <!-- Búsqueda de información del Negocio (Catálogo/Productos) -->
                <div class="flex-grow max-w-xs sm:max-w-sm md:max-w-md mx-2">
                    <form @submit.prevent="handleSearchInput" class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @input="handleSearchInput"
                            placeholder="Buscar licores..." 
                            class="w-full bg-slate-800/80 border border-slate-700 rounded-full pl-9 pr-4 py-1.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition"
                        />
                    </form>
                </div>
                
                <nav class="hidden lg:flex items-center gap-6 text-sm font-medium shrink-0">
                    <a href="#inicio" class="hover:text-amber-400 transition">Inicio</a>
                    <a href="#catalogo" class="hover:text-amber-400 transition">Catálogo</a>
                    <a href="#nosotros" class="hover:text-amber-400 transition">Nosotros</a>
                    <a href="#contacto" class="hover:text-amber-400 transition">Contacto</a>
                </nav>

                <div class="flex items-center gap-3 shrink-0">
                    <div v-if="canLogin" class="flex items-center gap-2">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="bg-amber-500 hover:bg-amber-600 text-[#0f172a] px-4 py-2 rounded-lg text-sm font-bold shadow transition">
                            Ir al Panel
                        </Link>

                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-semibold hover:text-amber-400 px-3 py-2 transition">
                                Iniciar Sesión
                            </Link>

                            <Link v-if="canRegister" :href="route('register')" class="bg-amber-500 hover:bg-amber-600 text-[#0f172a] px-4 py-2 rounded-lg text-sm font-bold shadow transition">
                                Registrarse
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section id="inicio" class="relative bg-[#0f172a] text-white py-20 overflow-hidden border-b border-slate-800">
            <!-- Background pattern -->
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#f59e0b_1px,transparent_1px)] [background-size:16px_16px]"></div>
            
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <span class="inline-block bg-amber-500/20 text-amber-400 text-xs px-3 py-1 rounded-full font-bold tracking-wider uppercase">
                        Exclusividad y Calidad
                    </span>
                    <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight">
                        Tu Licorería de Confianza a un Clic
                    </h1>
                    <p class="text-slate-300 text-lg leading-relaxed max-w-lg">
                        Explora nuestra exclusiva selección de bebidas nacionales e internacionales. Compra en línea con total seguridad o visítanos en nuestra tienda física.
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="#catalogo" class="bg-amber-500 hover:bg-amber-600 text-[#0f172a] px-6 py-3 rounded-lg font-bold shadow-lg transition text-center">
                            Ver Catálogo
                        </a>
                        <a href="#nosotros" class="border border-slate-600 hover:border-amber-400 px-6 py-3 rounded-lg font-bold transition text-center">
                            Conócenos
                        </a>
                    </div>
                </div>
                
                <!-- Hero Image Placeholder -->
                <div class="hidden md:flex justify-center">
                    <div class="relative w-80 h-96 bg-gradient-to-tr from-[#1e293b] to-[#0f172a] rounded-2xl border border-slate-700 shadow-2xl flex flex-col items-center justify-center p-6 overflow-hidden group">
                        <div class="absolute inset-0 bg-amber-500/5 group-hover:bg-amber-500/10 transition-colors"></div>
                        <svg class="w-16 h-16 text-amber-400 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a7 7 0 007-7V4H5v4a7 7 0 007 7zM12 15v6M9 21h6"></path>
                        </svg>
                        <span class="text-xl font-black tracking-wider text-amber-400">VINTAGE SELECTION</span>
                        <p class="text-slate-400 text-xs text-center mt-2 px-6">Licores añejados, destilados premium y cervezas seleccionadas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Public Catalog Section -->
        <section id="catalogo" class="py-16 bg-slate-50 flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center space-y-3 mb-12">
                    <h2 class="text-3xl font-black text-[#0f172a]">Bebidas Destacadas</h2>
                    <div class="w-16 h-1 bg-amber-500 mx-auto rounded-full"></div>
                    <p class="text-slate-500 text-sm max-w-md mx-auto">
                        Productos listos para entrega inmediata con el mejor control de stock.
                    </p>
                </div>

                <div v-if="filteredProductos.length > 0" class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <article v-for="producto in filteredProductos" :key="producto.id" class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col group">
                        <!-- Product Visual -->
                        <div class="h-48 bg-[#0f172a] flex items-center justify-center relative p-6 border-b border-slate-100">
                            <svg class="w-12 h-12 text-amber-400/85 filter drop-shadow" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V9a4 4 0 014-4h6a4 4 0 014 4v10a2 2 0 01-2 2zM9 5V3a1 1 0 011-1h4a1 1 0 011 1v2"></path>
                            </svg>
                            <div class="absolute top-3 right-3 bg-[#0f172a] text-amber-400 text-xs font-bold px-2 py-1 rounded border border-slate-700">
                                Bs {{ Number(producto.precio).toFixed(2) }}
                            </div>
                        </div>

                        <!-- Product Data -->
                        <div class="p-6 flex-grow flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-[#0f172a] group-hover:text-amber-600 transition">
                                    {{ producto.nombre }}
                                </h3>
                                <p class="text-slate-600 text-sm line-clamp-3">
                                    {{ producto.descripcion || 'Bebida premium disponible en nuestra licorería.' }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-100 text-xs">
                                <span class="text-slate-400 font-medium">Stock Disponible</span>
                                <span class="bg-slate-100 text-slate-700 font-bold px-2.5 py-1 rounded-full">
                                    {{ producto.stock?.cantidad || 0 }} u
                                </span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12 px-6 bg-white rounded-2xl border border-slate-200 shadow-sm max-w-md mx-auto space-y-4 animate-fade-in">
                    <svg class="w-12 h-12 text-slate-450 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">No se encontraron productos</h3>
                        <p class="text-xs text-slate-505 mt-1">No hay bebidas que coincidan con su criterio de búsqueda. Intente con otro término.</p>
                    </div>
                    <button 
                        @click="searchQuery = ''" 
                        class="inline-flex items-center text-xs font-bold bg-amber-500 hover:bg-amber-600 text-[#0f172a] px-4 py-2 rounded-lg shadow-sm transition"
                    >
                        Ver Todo el Catálogo
                    </button>
                </div>
            </div>
        </section>

        <!-- Nosotros Section -->
        <section id="nosotros" class="py-16 bg-[#0f172a] text-white border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <h2 class="text-3xl font-black text-amber-400">Nuestra Licorería</h2>
                    <p class="text-slate-300 leading-relaxed">
                        En **Licor Vintage**, nos apasiona ofrecer la mejor experiencia de compra. Operamos tanto de manera física como de forma digital, asegurando un inventario actualizado y un control meticuloso de cada botella.
                    </p>
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-xl">
                            <span class="block text-2xl font-black text-amber-400">100%</span>
                            <span class="text-xs text-slate-400">Seguridad en transacciones</span>
                        </div>
                        <div class="bg-slate-800/50 border border-slate-700 p-4 rounded-xl">
                            <span class="block text-2xl font-black text-amber-400">Garantía</span>
                            <span class="text-xs text-slate-400">Productos originales</span>
                        </div>
                    </div>
                </div>
                
                <!-- Info Grid -->
                <div class="bg-slate-800/30 border border-slate-700 p-8 rounded-2xl space-y-6">
                    <h3 class="text-xl font-bold text-amber-400">Información al Cliente</h3>
                    <ul class="space-y-4 text-sm text-slate-300">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Dirección: Av. Principal, Zona Central, Santa Cruz, Bolivia</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Horario: Lunes a Sábado - 10:00 AM a 11:00 PM</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span>Contacto: +591 76543210</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Footer with Page Visitor Counter -->
        <footer id="contacto" class="bg-[#0b0f19] text-slate-400 py-8 border-t border-slate-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-sm">
                    &copy; 2026 Licor Vintage. Todos los derechos reservados.
                </div>
                
                <!-- Page Visitor Counter Placeholder/Implementation -->
                <div class="bg-[#1e293b] border border-slate-700 rounded-lg px-4 py-2 text-xs flex items-center gap-2 text-slate-300">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>Visitas de la página: </span>
                    <span class="font-mono font-black text-amber-400 bg-[#0f172a] px-2 py-0.5 rounded border border-slate-800">
                        {{ $page.props.visitas_home || 1 }}
                    </span>
                </div>
            </div>
        </footer>

        <!-- Floating design and theme customizer component -->
        <Customizer />
    </div>
</template>

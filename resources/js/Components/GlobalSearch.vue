<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const search = ref('');
const results = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const debounceTimeout = ref(null);
const selectedProduct = ref(null);
const showModal = ref(false);
const searchContainer = ref(null);

const handleSearch = () => {
    if (debounceTimeout.value) {
        clearTimeout(debounceTimeout.value);
    }

    if (!search.value.trim()) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    debounceTimeout.value = setTimeout(async () => {
        try {
            const response = await fetch(`/api/search?query=${encodeURIComponent(search.value)}`);
            if (response.ok) {
                results.value = await response.json();
            } else {
                results.value = [];
            }
        } catch (error) {
            console.error('Error al buscar productos:', error);
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
};

const selectProduct = (product) => {
    selectedProduct.value = product;
    showModal.value = true;
    showDropdown.value = false;
    search.value = '';
};

const getQrUrl = (product) => {
    if (product.codigo_qr) return product.codigo_qr;
    const text = `Producto: ${product.nombre}\nCodigo: ${product.codigo_barra}\nPrecio: ${product.precio_venta} Bs\nStock: ${product.stock}`;
    return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(text)}`;
};

const handleClickOutside = (event) => {
    if (searchContainer.value && !searchContainer.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        showDropdown.value = false;
        showModal.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});

watch(search, () => {
    showDropdown.value = true;
    handleSearch();
});
</script>

<template>
    <div ref="searchContainer" class="relative w-full max-w-md mx-auto z-50">
        <!-- Input de búsqueda -->
        <div class="relative">
            <input
                v-model="search"
                type="text"
                placeholder="Buscar productos (nombre, barra, desc)..."
                class="w-full pl-10 pr-4 py-2 rounded-full border border-white/20 bg-white/10 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white/20 backdrop-blur-md transition-all duration-300 shadow-md text-sm"
                aria-label="Buscar productos"
                @focus="showDropdown = true"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div v-if="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <svg class="animate-spin h-5 w-5 text-white/60" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown de resultados -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showDropdown && (results.length > 0 || (search.trim() && !loading))"
                class="absolute right-0 left-0 mt-2 rounded-2xl border border-white/20 bg-slate-900/90 backdrop-blur-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto z-50 transition-all duration-300"
            >
                <div v-if="results.length > 0">
                    <button
                        v-for="product in results"
                        :key="product.id"
                        class="w-full text-left px-4 py-3 flex items-center gap-4 hover:bg-white/10 transition-colors duration-200 border-b border-white/5 last:border-0"
                        @click="selectProduct(product)"
                    >
                        <img
                            :src="product.imagen"
                            :alt="product.nombre"
                            class="w-10 h-10 rounded-lg object-cover bg-white/10 flex-shrink-0"
                        />
                        <div class="flex-grow min-w-0">
                            <h4 class="text-white font-medium text-sm truncate">{{ product.nombre }}</h4>
                            <p class="text-white/60 text-xs truncate">{{ product.descripcion || 'Sin descripción' }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-indigo-300 font-semibold text-sm block">{{ product.precio_venta }} Bs</span>
                            <span
                                :class="[
                                    'text-[10px] px-2 py-0.5 rounded-full font-medium inline-block',
                                    product.stock > 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'
                                ]"
                            >
                                Stock: {{ product.stock }}
                            </span>
                        </div>
                    </button>
                </div>
                <div v-else class="px-4 py-6 text-center text-white/60 text-sm">
                    No se encontraron productos para "{{ search }}"
                </div>
            </div>
        </transition>

        <!-- Modal de Detalle de Producto -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal && selectedProduct" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.self="showModal = false">
                <div class="bg-slate-900 border border-white/15 rounded-3xl w-full max-w-2xl overflow-hidden shadow-2xl transform transition-all duration-300 max-h-[90vh] overflow-y-auto flex flex-col md:flex-row">
                    <!-- Sección de Imagen / Izquierda -->
                    <div class="md:w-1/2 relative bg-black/30 flex items-center justify-center min-h-[250px]">
                        <img
                            :src="selectedProduct.imagen"
                            :alt="selectedProduct.nombre"
                            class="w-full h-full object-cover max-h-[400px]"
                        />
                        <span class="absolute top-4 left-4 bg-indigo-600/90 backdrop-blur-md text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg">
                            {{ selectedProduct.mililitros }} ml
                        </span>
                    </div>

                    <!-- Sección de Detalles / Derecha -->
                    <div class="md:w-1/2 p-6 flex flex-col justify-between text-white">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-bold text-white tracking-wide">{{ selectedProduct.nombre }}</h3>
                                <button @click="showModal = false" class="text-white/60 hover:text-white bg-white/5 hover:bg-white/15 p-1.5 rounded-full transition-all duration-200">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-white/70 text-sm mb-4 leading-relaxed font-light">
                                {{ selectedProduct.descripcion || 'Este producto no cuenta con una descripción detallada en este momento.' }}
                            </p>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="bg-white/5 p-3 rounded-2xl border border-white/10">
                                    <span class="text-white/50 text-[10px] uppercase font-bold tracking-wider block">Precio de Venta</span>
                                    <span class="text-lg font-extrabold text-indigo-300">{{ selectedProduct.precio_venta }} Bs</span>
                                </div>
                                <div class="bg-white/5 p-3 rounded-2xl border border-white/10">
                                    <span class="text-white/50 text-[10px] uppercase font-bold tracking-wider block">Stock Disponible</span>
                                    <span :class="['text-lg font-extrabold', selectedProduct.stock > 0 ? 'text-emerald-400' : 'text-rose-400']">
                                        {{ selectedProduct.stock }} uds
                                    </span>
                                </div>
                            </div>

                            <div class="bg-white/5 p-3 rounded-2xl border border-white/10 mb-4 flex items-center justify-between">
                                <div>
                                    <span class="text-white/50 text-[10px] uppercase font-bold tracking-wider block">Código de Barras</span>
                                    <span class="font-mono text-xs text-white/90">{{ selectedProduct.codigo_barra }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Código QR -->
                        <div class="border-t border-white/10 pt-4 flex flex-col items-center">
                            <span class="text-white/50 text-[10px] uppercase font-bold tracking-wider mb-2">Escanear Código QR</span>
                            <div class="bg-white p-2 rounded-2xl shadow-xl flex items-center justify-center">
                                <img
                                    :src="getQrUrl(selectedProduct)"
                                    alt="Código QR del Producto"
                                    class="w-28 h-28"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

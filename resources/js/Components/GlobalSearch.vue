<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

defineProps({
    isSidebar: {
        type: Boolean,
        default: false
    }
});

const page = usePage();
const search = ref('');
const results = ref({
    productos: [],
    usuarios: [],
    proveedores: [],
    promociones: []
});
const loading = ref(false);
const showDropdown = ref(false);
const debounceTimeout = ref(null);
const selectedProduct = ref(null);
const showModal = ref(false);
const searchContainer = ref(null);
const searchInput = ref(null);

const normalizeString = (str) => {
    if (!str) return '';
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
};

const filteredMenus = computed(() => {
    const query = normalizeString(search.value.trim());
    if (!query) return [];
    const menus = page.props.menu || [];
    return menus.filter(item => normalizeString(item.label).includes(query));
});

const hasResults = computed(() => {
    return filteredMenus.value.length > 0 ||
        (results.value.productos && results.value.productos.length > 0) ||
        (results.value.usuarios && results.value.usuarios.length > 0) ||
        (results.value.proveedores && results.value.proveedores.length > 0) ||
        (results.value.promociones && results.value.promociones.length > 0);
});

const handleSearch = () => {
    if (debounceTimeout.value) {
        clearTimeout(debounceTimeout.value);
    }

    if (!search.value.trim()) {
        results.value = { productos: [], usuarios: [], proveedores: [], promociones: [] };
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
                results.value = { productos: [], usuarios: [], proveedores: [], promociones: [] };
            }
        } catch (error) {
            console.error('Error al realizar la búsqueda global:', error);
            results.value = { productos: [], usuarios: [], proveedores: [], promociones: [] };
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

const navigateToMenu = (routeName) => {
    showDropdown.value = false;
    search.value = '';
    router.visit(route(routeName));
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

const handleGlobalKeydown = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        if (searchInput.value) {
            searchInput.value.focus();
            showDropdown.value = true;
        }
    }
    if (event.key === 'Escape') {
        showDropdown.value = false;
        if (searchInput.value) {
            searchInput.value.blur();
        }
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('keydown', handleGlobalKeydown);
});

watch(search, () => {
    showDropdown.value = true;
    handleSearch();
});
</script>

<template>
    <div 
        ref="searchContainer" 
        class="relative transition-all duration-300 z-50 select-none"
        :class="isSidebar ? 'w-full' : 'w-full md:w-32 md:focus-within:w-56'"
    >
        <!-- Caja de entrada de texto (Inline) -->
        <div class="relative">
            <input
                ref="searchInput"
                v-model="search"
                type="text"
                placeholder="Buscar..."
                class="w-full pl-8 pr-4 py-1.5 rounded-full border border-white/15 bg-white/5 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:bg-white/10 backdrop-blur-sm transition-all duration-300 shadow-sm text-xs cursor-text"
                aria-label="Buscar productos"
                @focus="showDropdown = true"
            />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-3.5 w-3.5 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div v-if="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <svg class="animate-spin h-3.5 w-3.5 text-[var(--accent)]" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown de resultados (Inline, posicionado debajo del input) -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showDropdown && (hasResults || (search.trim() && !loading))"
                class="absolute left-0 mt-2 rounded-xl border border-white/15 bg-slate-950/95 backdrop-blur-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto z-50 w-64 md:w-80"
                :class="isSidebar ? 'left-0 right-0 w-full' : 'right-auto md:right-0 md:left-auto'"
            >
                <div v-if="hasResults" class="p-2 space-y-3">
                    
                    <!-- CATEGORÍA: Módulos -->
                    <div v-if="filteredMenus.length > 0">
                        <span class="text-[9px] uppercase tracking-wider text-white/40 px-2.5 font-bold block mb-1">Módulos</span>
                        <button
                            v-for="item in filteredMenus"
                            :key="item.routeName"
                            class="w-full text-left px-2.5 py-1.5 flex items-center gap-2 hover:bg-white/10 rounded-lg transition text-xs text-white cursor-pointer"
                            @click="navigateToMenu(item.routeName)"
                        >
                            <svg class="w-3.5 h-3.5 text-[var(--accent)] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span>Ir a {{ item.label }}</span>
                        </button>
                    </div>

                    <!-- CATEGORÍA: Productos -->
                    <div v-if="results.productos && results.productos.length > 0">
                        <span class="text-[9px] uppercase tracking-wider text-white/40 px-2.5 font-bold block mb-1">Productos</span>
                        <button
                            v-for="product in results.productos"
                            :key="product.id"
                            class="w-full text-left px-2.5 py-2 flex items-center gap-3 hover:bg-white/10 rounded-lg transition border-b border-white/5 last:border-0 cursor-pointer"
                            @click="selectProduct(product)"
                        >
                            <img
                                :src="product.imagen"
                                :alt="product.nombre"
                                class="w-8 h-8 rounded-lg object-cover bg-white/5 shrink-0 border border-white/10"
                            />
                            <div class="flex-grow min-w-0">
                                <h4 class="text-white font-semibold text-[11px] truncate">{{ product.nombre }}</h4>
                                <p class="text-white/50 text-[9px] truncate mt-0.5">{{ product.descripcion || 'Sin descripción' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[var(--accent)] font-bold text-[11px] block">{{ product.precio_venta }} Bs</span>
                            </div>
                        </button>
                    </div>

                    <!-- CATEGORÍA: Usuarios -->
                    <div v-if="results.usuarios && results.usuarios.length > 0">
                        <span class="text-[9px] uppercase tracking-wider text-white/40 px-2.5 font-bold block mb-1">Usuarios</span>
                        <button
                            v-for="u in results.usuarios"
                            :key="u.id"
                            class="w-full text-left px-2.5 py-2 flex items-center gap-2.5 hover:bg-white/10 rounded-lg transition border-b border-white/5 last:border-0 cursor-pointer text-xs text-white"
                            @click="navigateToMenu('usuarios.index')"
                        >
                            <div class="w-7 h-7 rounded-full bg-indigo-600/30 border border-indigo-500/30 flex items-center justify-center font-bold text-indigo-300 text-[10px] shrink-0">
                                {{ u.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <h4 class="font-semibold text-[11px] truncate text-white">{{ u.name }}</h4>
                                <p class="text-white/40 text-[9px] truncate">CI: {{ u.ci || 'N/A' }} | {{ u.email }}</p>
                            </div>
                        </button>
                    </div>

                    <!-- CATEGORÍA: Proveedores -->
                    <div v-if="results.proveedores && results.proveedores.length > 0">
                        <span class="text-[9px] uppercase tracking-wider text-white/40 px-2.5 font-bold block mb-1">Proveedores</span>
                        <button
                            v-for="p in results.proveedores"
                            :key="p.id"
                            class="w-full text-left px-2.5 py-2 flex items-center gap-2.5 hover:bg-white/10 rounded-lg transition border-b border-white/5 last:border-0 cursor-pointer text-xs text-white"
                            @click="navigateToMenu('proveedores.index')"
                        >
                            <div class="w-7 h-7 rounded-full bg-emerald-600/30 border border-emerald-500/30 flex items-center justify-center font-bold text-emerald-300 text-[10px] shrink-0">
                                {{ p.nombre.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <h4 class="font-semibold text-[11px] truncate text-white">{{ p.nombre }}</h4>
                                <p class="text-white/40 text-[9px] truncate">Tel: {{ p.telefono || 'N/A' }}</p>
                            </div>
                        </button>
                    </div>

                    <!-- CATEGORÍA: Promociones -->
                    <div v-if="results.promociones && results.promociones.length > 0">
                        <span class="text-[9px] uppercase tracking-wider text-white/40 px-2.5 font-bold block mb-1">Promociones</span>
                        <button
                            v-for="promo in results.promociones"
                            :key="promo.id"
                            class="w-full text-left px-2.5 py-2 flex items-center gap-2.5 hover:bg-white/10 rounded-lg transition border-b border-white/5 last:border-0 cursor-pointer text-xs text-white"
                            @click="navigateToMenu('promociones.index')"
                        >
                            <div class="w-7 h-7 rounded-lg bg-rose-600/30 border border-rose-500/30 flex items-center justify-center font-bold text-rose-300 text-[10px] shrink-0">
                                %
                            </div>
                            <div class="flex-grow min-w-0">
                                <h4 class="font-semibold text-[11px] truncate text-white">{{ promo.nombre_promo }}</h4>
                                <p class="text-white/40 text-[9px] truncate">Cód: {{ promo.codigo_promo }} | -{{ promo.descuento }}{{ promo.tipo_descuento === 'porcentaje' ? '%' : ' Bs' }}</p>
                            </div>
                        </button>
                    </div>

                </div>
                <div v-else class="px-4 py-6 text-center text-white/40 text-xs">
                    No se encontraron resultados para "<strong class="text-white">{{ search }}</strong>"
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
                    <!-- Sección de Imagen -->
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

                    <!-- Sección de Detalles -->
                    <div class="md:w-1/2 p-6 flex flex-col justify-between text-white">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-bold text-white tracking-wide">{{ selectedProduct.nombre }}</h3>
                                <button @click="showModal = false" class="text-white/60 hover:text-white bg-white/5 hover:bg-white/15 p-1.5 rounded-full transition-all duration-200 cursor-pointer">
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

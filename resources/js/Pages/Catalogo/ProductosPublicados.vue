<script setup>
import { reactive } from 'vue';

const props = defineProps({
    productos: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['add-to-cart']);

const quantities = reactive({});

const quantity = (producto) => quantities[producto.id] || 1;
const maxStock = (producto) => producto.stock_actual?.stock || producto.stockActual?.stock || 0;

const increase = (producto) => {
    quantities[producto.id] = Math.min(quantity(producto) + 1, maxStock(producto));
};

const decrease = (producto) => {
    quantities[producto.id] = Math.max(quantity(producto) - 1, 1);
};

const triggerAddToCart = (producto) => {
    const qty = quantity(producto);
    const stock = maxStock(producto);
    const foto = producto.fotos?.length ? producto.fotos[0] : (producto.imagen || '');

    emit('add-to-cart', {
        id: producto.id,
        nombre: producto.nombre,
        precio_venta: Number(producto.precio_venta),
        cantidad: qty,
        stock: stock,
        imagen: foto
    });

    // Resetear cantidad a 1 después de agregar
    quantities[producto.id] = 1;
};
</script>

<template>
    <div v-if="!productos || productos.length === 0" class="col-span-full text-center py-20">
        <p class="text-[var(--text-secondary)] text-lg">No hay productos disponibles en este momento.</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" v-else>
        <template v-for="producto in productos" :key="producto.id">
            <!-- Tarjeta Premium con Glassmorphism -->
            <article
                v-if="producto.publicado"
                class="product-card overflow-hidden rounded-3xl flex flex-col justify-between"
            >
                <div>
                    <!-- Imagen del Producto -->
                    <div class="aspect-[4/3] relative overflow-hidden group image-wrapper">
                        <img
                            v-if="producto.fotos?.length"
                            :src="producto.fotos[0]"
                            :alt="producto.nombre"
                            class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                        >
                        <img
                            v-else-if="producto.imagen"
                            :src="producto.imagen"
                            :alt="producto.nombre"
                            class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                        >
                        <div
                            v-else
                            class="flex h-full items-center justify-center text-lg font-semibold text-amber-200 p-4 text-center select-none placeholder-bg"
                        >
                            {{ producto.nombre }}
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-350"></div>
                        <span class="badge absolute top-4 left-4 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow-md">
                            {{ producto.mililitros }} ml
                        </span>
                    </div>

                    <!-- Datos del Producto -->
                    <div class="p-5 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-bold text-[var(--text-primary)] text-base tracking-tight truncate flex-1" :title="producto.nombre">
                                {{ producto.nombre }}
                            </h2>
                            <div class="price-tag font-extrabold text-base flex-shrink-0">
                                {{ Number(producto.precio_venta).toFixed(2) }} Bs
                            </div>
                        </div>
                        <p class="text-xs text-[var(--text-secondary)] line-clamp-2 leading-relaxed min-h-[32px] font-light">
                            {{ producto.descripcion || 'Bebida exclusiva disponible en catálogo.' }}
                        </p>
                    </div>
                </div>

                <!-- Footer de Tarjeta con Controles -->
                <div class="p-5 pt-0">
                    <div class="flex items-center justify-between divider-top pt-4 mt-2">
                        <span class="text-xs text-[var(--text-secondary)] font-medium">
                            Disponibles: <strong class="text-[var(--text-primary)] font-bold">{{ maxStock(producto) }}</strong>
                        </span>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="qty-btn w-7 h-7 rounded-lg text-xs flex items-center justify-center font-bold transition"
                                @click="decrease(producto)"
                            >
                                -
                            </button>
                            <span class="w-6 text-center font-bold text-xs text-[var(--text-primary)]">
                                {{ quantity(producto) }}
                            </span>
                            <button
                                type="button"
                                class="qty-btn w-7 h-7 rounded-lg text-xs flex items-center justify-center font-bold transition disabled:opacity-20 disabled:cursor-not-allowed"
                                :disabled="quantity(producto) >= maxStock(producto)"
                                @click="increase(producto)"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="add-btn w-full mt-4 text-white text-xs font-bold py-2.5 rounded-xl transition shadow-lg cursor-pointer flex items-center justify-center gap-1.5"
                        @click="triggerAddToCart(producto)"
                    >
                        <span>Agregar al Carrito</span>
                    </button>
                </div>
            </article>
        </template>
    </div>
</template>

<style scoped>
.product-card {
    background: rgba(43, 17, 21, 0.6);
    border: 1px solid rgba(120, 53, 4, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4), 0 0 30px rgba(217, 119, 6, 0.08);
}

.image-wrapper {
    background: rgba(0, 0, 0, 0.3);
}

.placeholder-bg {
    background: linear-gradient(135deg, rgba(43, 17, 21, 0.8) 0%, rgba(120, 53, 4, 0.3) 100%);
}

.badge {
    background: rgba(99, 102, 241, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.price-tag {
    color: #a5b4fc; /* indigo-300 */
}

.divider-top {
    border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.qty-btn {
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-primary);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.qty-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.15);
}

.add-btn {
    background: var(--accent);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.add-btn:hover {
    background: var(--accent-hover);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    transform: translateY(-1px);
}

/* Soporte para tema light */
:global(.theme-adults.theme-light) .product-card {
    background: rgba(245, 245, 244, 0.75);
    border-color: rgba(120, 53, 4, 0.15);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

:global(.theme-youth) .product-card {
    background: rgba(22, 14, 55, 0.7);
    border-color: rgba(236, 72, 153, 0.2);
}

:global(.theme-kids) .product-card {
    background: rgba(20, 33, 61, 0.75);
    border-color: rgba(255, 183, 3, 0.25);
}
</style>

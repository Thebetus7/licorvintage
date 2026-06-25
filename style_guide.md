# Guía de Estilo Visual Premium - Licor Vintage

Esta guía documenta los principios de diseño, colores y componentes de interfaz establecidos en la landing page ([Welcome.vue](file:///c:/EDBERTO/ULT%20SEMESTRE/TW/P2/licorvintage/resources/js/Pages/Welcome.vue)) para mantener la consistencia visual premium en todo el sistema.

---

## 🎨 Sistema de Colores y Fondos

El diseño de **Licor Vintage** utiliza una paleta oscura, cálida y sofisticada inspirada en bodegas y licores exclusivos.

### Colores Base
*   **Fondo Principal**: `#140b0d` (Borgoña oscuro profundo)
*   **Contenedores / Tarjetas**: `#1a0e11` con opacidad del 80% (`bg-[#1a0e11]/80 backdrop-blur-md`)
*   **Acentos Dorados**: `amber-500` (`#d97706`) y `amber-600` (`#b45309`)
*   **Textos**: `stone-100` (`#f5f5f4`) para títulos, `stone-300` (`#d6d3d1`) para párrafos, y `amber-200` (`#fde68a`) para llamadas de atención.

### Luces de Fondo (Efecto Blur Glow)
Para dar profundidad, coloca los siguientes destellos difusores detrás de tus contenedores principales:
```html
<!-- Luces de fondo premium -->
<div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-amber-500/5 blur-[120px] pointer-events-none"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[60%] h-[60%] rounded-full bg-rose-950/20 blur-[150px] pointer-events-none"></div>
```

---

## 📦 Componentes del Formulario Estilizados

Todos los campos del formulario deben adoptar el tema oscuro para evitar contrastes altos y molestos.

### 1. Entradas de Texto (`TextInput`)
Fondo oscuro translúcido con bordes piedra sutiles y enfoque dorado:
```html
<input class="bg-[#241518]/60 border border-stone-800 text-stone-100 placeholder-stone-500 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm w-full py-2 px-3 focus:outline-none">
```

### 2. Selectores (`select`)
Igual consistencia que el input de texto, con fondo de opciones explícito:
```html
<select class="w-full rounded-md bg-[#241518]/60 border border-stone-800 text-stone-100 focus:border-amber-500 focus:ring-amber-500 py-2 px-3 focus:outline-none">
    <option value="vendedor" class="bg-[#1c0e11] text-stone-200">Vendedor</option>
</select>
```

### 3. Botones Principales (`PrimaryButton`)
Botón con degradado ámbar y sombra sutil:
```html
<button class="bg-gradient-to-r from-amber-600 to-amber-700 text-white hover:from-amber-500 hover:to-amber-600 focus:ring-amber-500 shadow-md shadow-amber-950/20">
    Guardar
</button>
```

### 4. Botones Secundarios / Opciones (`SecondaryButton` / `EditUserButton`)
Botón borgoña translúcido con contorno y texto dorado:
```html
<button class="bg-[#2b1115]/30 border border-stone-800 hover:bg-[#2b1115]/60 text-amber-200 focus:ring-amber-500">
    Editar
</button>
```

---

## ✨ Tarjetas de Características (Glassmorphism)
Para listar resúmenes, módulos o estadísticas rápidas en paneles y landing pages, utiliza este bloque con bordes dinámicos que reaccionan al pasar el cursor:
```html
<div class="p-6 rounded-xl border border-stone-900 bg-stone-950/30 backdrop-blur-sm hover:border-amber-900/40 hover:bg-[#1a0e11]/20 transition duration-300 shadow-md shadow-black/10">
    <div class="h-10 w-10 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-400 mb-4 font-bold text-lg">
        🍷
    </div>
    <h3 class="text-lg font-semibold text-white mb-2">Título de Muestra</h3>
    <p class="text-stone-400 text-sm leading-relaxed">Descripción corta de la ventaja o característica.</p>
</div>
```

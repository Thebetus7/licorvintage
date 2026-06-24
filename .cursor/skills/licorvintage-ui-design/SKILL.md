---
name: licorvintage-ui-design
description: >-
  UI/UX design standards for Licor Vintage (Laravel + Inertia + Vue 3 + Tailwind).
  Use when building or redesigning modals, forms, tables, navigation, or product/inventory screens.
---

# Licor Vintage — UI Design Skill

Guía viva de diseño para el proyecto. Amplía esta sección conforme el producto evolucione.

## Principios base

- **Una tarea por modal**: formularios largos → cuerpo con scroll interno, no página completa dentro del modal.
- **Acciones siempre visibles**: footer fijo con Cancelar + acción primaria; nunca enterrar botones bajo scroll.
- **Marco visible en cualquier pantalla**: `max-h-[min(90vh,720px)]`, `overflow-hidden` en contenedor, scroll solo en body.
- **Inputs proporcionados al dato**: no usar `w-full` en campos cortos (ml, costo, stock). Usar `max-w-*` según tipo.
- **Secciones con jerarquía**: títulos `text-xs uppercase tracking-wide text-amber-800`, bloques con `space-y-3` y `gap-3`.

## Modales (`DialogModal`)

Usar prop `scrollable` en formularios:

```vue
<DialogModal max-width="lg" scrollable>
```

Estructura esperada:

| Zona | Clases / comportamiento |
|------|-------------------------|
| Header | `shrink-0 border-b` — título claro |
| Body | `flex-1 min-h-0 overflow-y-auto` — único scroll |
| Footer | `shrink-0 border-t bg-stone-50` — acciones + toggles críticos |

Reglas UX (modales):

- Ancho modal formulario: `max-width="lg"` (~512px), no `2xl` salvo tablas embebidas.
- Máximo ~25% viewport de altura útil antes de forzar scroll interno.
- Escape, overlay click y botón Cancelar siempre disponibles.
- No anidar modales; cerrar el secundario antes de abrir otro.

## Formularios

### Anchos recomendados

| Campo | Ancho |
|-------|-------|
| Nombre, descripción | `max-w-md` / `max-w-lg` |
| Código de barras | `max-w-md` + botón auxiliar |
| Mililitros, stock min/max | `max-w-[7rem]` – `w-28` |
| Costo, precio | `max-w-[9rem]` |
| Textarea descripción | `max-w-lg`, `rows="3"` |

### Agrupación

1. Fotos / media  
2. Identificación (nombre, código, ml)  
3. Precios (costo + venta en 2 columnas)  
4. Descripción  
5. Stock (solo campos editables según rol/flujo)

### Toggle importante en footer

Decisiones de visibilidad/negocio (ej. **Publicado en catálogo**) van en el **footer izquierdo**, no en el body:

- Switch visual + título + subtítulo explicativo.
- Estado activo: `border-emerald-300 bg-emerald-50`.
- Estado inactivo: `border-stone-300 bg-white`.

## Imágenes de producto

- Subir a `public/media/productos/` vía endpoint dedicado (`productos.imagen.store`).
- Comprimir en cliente: JPEG ~0.65, ancho máx. 800px antes de subir.
- Flujos: **Tomar foto** (getUserMedia + canvas) e **Importar** (file input).
- Máximo 6 fotos; primera = portada (`fotos[0]` → `imagen` legacy).
- Mostrar grid de miniaturas con badge "Portada" en índice 0.

## Código de barras / QR

- Campo texto + botón **Escanear** que abre modal con cámara (`html5-qrcode`).
- Preview QR generado del valor del campo (no subir QR como imagen obligatoria).
- Formatos: CODE_128, EAN_13, UPC, QR_CODE.

## Paleta Licor Vintage

- Fondo app: `bg-stone-100`
- Marca / nav: `#2b1115`
- Acento: `amber-700`, `amber-800`
- Éxito / publicado: `emerald-50`, `emerald-500`
- Texto secundario: `text-stone-500`, `text-stone-600`

## Checklist antes de entregar UI

- [ ] Modal: scroll interno, footer fijo, marco visible en móvil y desktop
- [ ] Inputs no más anchos de lo necesario
- [ ] Secciones etiquetadas y espaciado consistente (`space-y-5` entre bloques)
- [ ] Toggle de negocio crítico en footer si aplica
- [ ] Fotos: compresión + ruta pública + feedback de subida/errores
- [ ] Escáner: permisos de cámara manejados con mensaje claro
- [ ] `npm run dev` activo en desarrollo (assets Vue)

## Ampliaciones futuras

Añade aquí nuevas reglas cuando definas:

- Estilos de tablas CRUD  
- Dashboard KPI cards  
- Catálogo cliente (ecommerce)  
- Punto de venta / caja  

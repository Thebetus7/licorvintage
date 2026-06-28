# Índice de Documentación de Inventarios por Módulo (CU4)

**Ruta del archivo:** `docs/inventario/index.md`

Este índice sirve como portada de navegación para comprender la lógica técnica del **CU4: Gestión de Inventarios** a través de la perspectiva y estructura real del sistema de pestañas de la aplicación de **Licorvintage**.

---

## 📌 Guías y Lógica por Pestaña de Módulo

Haz clic en los enlaces para acceder al flujo, la lógica y los datos (entradas/salidas) de cada pestaña:

1.  ### 📊 [Pestaña 1: Resumen (Dashboard de Inventario)](01_resumen.md)
    *   Muestra el estado actual del almacén en dinero y desabastecimiento.
    *   Lista compacta de los últimos 10 movimientos.

2.  ### 📥📤 [Pestaña 2: Movimientos (Bitácora de Entradas/Salidas)](02_movimientos.md)
    *   Lógica y flujos técnicos para el registro de ingresos y salidas (devoluciones, mermas, etc.).
    *   Filtros y paginación de la bitácora.

3.  ### 📋 [Pestaña 3: Kardex (Control de Saldos y Costos Ponderados)](03_kardex.md)
    *   Hoja de vida histórica individual de un producto seleccionado.
    *   Trazabilidad de saldos y costos acumulados en el tiempo.

4.  ### 🧮 [Pestaña 4: Valorización (Costo Promedio Ponderado - CPP)](04_valorizacion.md)
    *   Cálculo del Costo Promedio Ponderado ante compras.
    *   Fórmula y lógica del cálculo de valor del activo de licores en stock.

5.  ### 🔍 [Pestaña 5: Conteo (Auditoría e Inventario Físico)](05_conteo.md)
    *   Registro de stock físico en estanterías.
    *   Fórmula de cálculo de diferencias y ejecución de auto-ajustes en base de datos.

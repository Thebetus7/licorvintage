# Licor Vintage — Guía de Comandos y Uso

Este es un sistema de gestión para licorerías que funciona como **tienda online** (ecommerce) y **punto de venta** (caja, inventario, compras, promociones y reportes). Desarrollado con Laravel 12, Jetstream 5.5, Inertia.js, Vue 3 y Tailwind CSS.

A continuación se detallan todos los comandos del proyecto y su función:

---

## 🛠️ Configuración Inicial del Entorno

Antes de iniciar el proyecto, se deben configurar las variables de entorno y los límites de tiempo.

### Copiar archivo de configuración de entorno
Crea el archivo `.env` a partir de la plantilla del proyecto para que puedas configurar tus credenciales de base de datos.
```bash
copy .env.example .env
```

### Incrementar el límite de tiempo de Composer
Evita errores de interrupción (timeout) al instalar paquetes pesados en sistemas Windows.
```bash
composer config --global process-timeout 2000
```

---

## 🚀 Inicialización del Proyecto

Comandos para instalar dependencias y dejar la base de datos lista para su uso.

### Instalar dependencias de PHP
Descarga e instala todas las librerías de backend necesarias para el proyecto.
```bash
composer install
```

### Generar clave de encriptación de la aplicación
Genera la clave única en el archivo `.env` necesaria para cifrar sesiones y cookies.
```bash
php artisan key:generate
```

### Instalar dependencias de Javascript (Frontend)
Descarga e instala todas las librerías de Node.js necesarias para el funcionamiento de Vue 3.
```bash
npm install
```

### Ejecutar migraciones y poblar la base de datos (Seeders)
Limpia la base de datos, crea todas las tablas desde cero y genera los roles del sistema (`propietario`, `vendedor`, `cliente`) junto con el usuario administrador inicial (`admin@gmail.com` / `123456789`).
```bash
php artisan migrate:fresh --seed
```

### Compilar recursos para producción
Compila y optimiza los archivos Javascript y CSS con Vite para el despliegue en producción.
```bash
npm run build
```

### ⚡ Inicialización automática (Todo en uno)
Ejecuta de forma secuencial la instalación de dependencias, copia de `.env`, generación de clave, migración de tablas, instalación de Node y compilación de Vite en un solo comando.
```bash
composer setup
```

---

## 💻 Ejecución en Desarrollo

Comandos para poner en marcha el servidor de desarrollo local.

### Ejecutar el entorno completo en paralelo
Inicia el servidor web, el procesador de colas (queue listener) y el servidor de desarrollo de Vite de forma simultánea.
```bash
composer dev
```

### Iniciar servidor web de Laravel (individual)
Arranca únicamente el servidor de backend de PHP local.
```bash
php artisan serve
```

### Iniciar servidor de desarrollo de Vite (individual)
Arranca el compilador en tiempo real del frontend para reflejar cambios en las vistas de Vue de forma instantánea.
```bash
npm run dev
```

---

## 🧪 Calidad de Código y Pruebas

Comandos para formatear y verificar la estabilidad de la aplicación.

### Formatear estilo de código PHP
Ejecuta Laravel Pint para formatear y corregir automáticamente el estilo de código siguiendo el estándar PSR-12.
```bash
vendor/bin/pint
```

### Ejecutar pruebas automatizadas
Limpia la caché de configuración y ejecuta el conjunto de pruebas unitarias y de integración.
```bash
composer test
```

### Ejecutar pruebas directamente con PHPUnit
Arranca la suite de pruebas configurada en el archivo `phpunit.xml`.
```bash
vendor/bin/phpunit
```

---

## 💾 Base de Datos

Comandos directos para gestionar las tablas y registros.

### Poblar la base de datos con registros de prueba
Ejecuta únicamente los sembradores (seeders) para insertar datos en las tablas.
```bash
php artisan db:seed
```

# Configuración en Firebase Console

**Ruta del archivo:** `docs/firebase_auth/02_firebase_console.md`

Este documento explica cómo configurar el proyecto y habilitar los servicios necesarios en Firebase Console para que la autenticación funcione correctamente.

---

## 1. Crear el Proyecto y la Aplicación Web

El proyecto y la aplicación web ya se han inicializado mediante las herramientas MCP de la plataforma:
* **Project ID:** `licorvintage-tw-p2`
* **Display Name:** `Licorvintage Auth Project`
* **App Display Name:** `Licorvintage Web App`

Si necesitas acceder a la consola web de Firebase:
1. Ve a [Firebase Console](https://console.firebase.google.com/).
2. Inicia sesión con la cuenta de Google registrada (por ejemplo, la misma utilizada en el MCP).
3. Busca y selecciona el proyecto **Licorvintage Auth Project**.

---

## 2. Habilitar Firebase Authentication y Proveedor Google

Para que el inicio de sesión funcione, debes asegurarte de que el servicio de autenticación y el proveedor de Google estén activos en el proyecto de Firebase:

1. En el menú lateral izquierdo de la consola, navega a **Build** > **Authentication** (Construir > Autenticación).
2. Haz clic en **Get Started** (Comenzar) si es la primera vez que entras.
3. En la pestaña **Sign-in method** (Método de inicio de sesión), haz clic en **Add new provider** (Agregar nuevo proveedor).
4. Selecciona **Google** de la lista de proveedores.
5. Habilita el interruptor en la esquina superior derecha.
6. Configura lo siguiente:
   * **Project public-facing name** (Nombre público del proyecto): Elige un nombre descriptivo para los usuarios (ej. `Licorvintage Store`).
   * **Project support email** (Correo electrónico de asistencia del proyecto): Selecciona tu correo de administrador de la lista.
7. Haz clic en **Save** (Guardar).

---

## 3. Configurar Dominios Autorizados (OAuth Redirect) y Solución de Errores

Si al intentar iniciar sesión en tu entorno de desarrollo o producción te salta el error **`auth/unauthorized-domain`** en rojo debajo del botón de Google, significa que el dominio o la dirección IP desde la que estás accediendo a la web no se encuentra autorizada en la consola de Firebase.

Por defecto, Firebase Auth suele agregar de manera automática `localhost`, pero a veces requiere registrar explícitamente la dirección IP de tu servidor de desarrollo (como `127.0.0.1`).

### Pasos para Configurar y Autorizar Dominios:
1. Dentro de la sección de **Authentication** en la consola de Firebase.
2. Haz clic en la pestaña **Configuración** (Settings) en la barra superior (se encuentra al lado de *Uso*).
3. En el menú de opciones de la izquierda, haz clic en **Dominios autorizados** (Authorized domains).
4. Haz clic en el botón **Agregar dominio** (Add domain).
5. Escribe la dirección exacta que utilizas en la barra de tu navegador (sin `http://` ni el puerto). Por ejemplo:
   - Si tu URL en el navegador es `http://127.0.0.1:8000/`, debes agregar: **`127.0.0.1`**
   - Si tu URL en el navegador es `http://localhost:8000/`, debes agregar: **`localhost`**
   - Si estás en producción como `https://mi-licoreria.com/`, debes agregar: **`mi-licoreria.com`**
6. Haz clic en **Agregar** (Add) para guardar los cambios.
7. Refresca la aplicación en tu navegador e intenta iniciar sesión de nuevo.

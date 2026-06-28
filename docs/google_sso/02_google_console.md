# Configuración en Google Cloud Console (Guía Definitiva)

**Ruta del archivo:** `docs/google_sso/02_google_console.md`

Este documento describe paso a paso cómo configurar la plataforma de Google Cloud para desarrollo local y cómo prepararla para producción.

---

## 1. Mapa de la Interfaz (Google Auth Platform)

En la nueva consola de Google Cloud, verás un menú lateral izquierdo compuesto únicamente por iconos. Esto es lo que significa cada uno y qué debes hacer en ellos:

*   🎨 **Icono 2 (Paleta de pintura / Información de la marca)**: Aquí configuras los datos básicos de tu aplicación (Nombre, correo de soporte).
*   👤 **Icono 3 (Clientes / Credentials)**: Aquí creas y obtienes las llaves de desarrollo y producción (`Client ID` y `Client Secret`).
*   👥 **Icono 4 (Audiencia / Usuarios de prueba)**: **Obligatorio para Desarrollo**. Aquí registras las cuentas de Gmail autorizadas para probar el inicio de sesión.
*   ⚙️➡️ **Icono 5 (Datos compartidos)** / **Icono 7 (Configuración)**: **IGNORAR EN DESARROLLO**. Muestran avisos de verificación de marca o configuración avanzada. **No necesitas tocar nada aquí**, ya que Google autoriza automáticamente los permisos básicos de inicio de sesión (`email` y `profile`) de forma nativa sin verificación.

---

## 2. Pasos para Configurar tu Entorno Local (Desarrollo)

Sigue exactamente estos tres pasos en la consola:

### Paso A: Registrar la Marca (Icono 2 🎨)
1. Haz clic en el **Icono 2 (Información de la marca)**.
2. Llena los únicos campos obligatorios:
   *   **Nombre de la aplicación**: `Licorvintage`
   *   **Correo electrónico de asistencia al usuario**: Selecciona tu correo de Gmail.
   *   **Información de contacto del desarrollador**: Escribe tu correo de Gmail al final.
3. Desplázate al final de la página y haz clic en el botón **Guardar** (Save).

### Paso B: Crear las Llaves del Servidor (Icono 3 👤)
1. Haz clic en el **Icono 3 (Clientes)**.
2. Haz clic en la parte superior en **Crear cliente** (o *Crear credenciales > ID de cliente de OAuth*).
3. Rellena los datos de la siguiente manera:
   *   **Tipo de aplicación**: Selecciona **Aplicación web**.
   *   **Nombre**: Escribe `Licorvintage Local`.
   *   **Orígenes autorizados de JavaScript**: Haz clic en *Agregar URI* y escribe: `http://127.0.0.1:8000`
   *   **URIs de redireccionamiento autorizados**: Haz clic en *Agregar URI* y escribe exactamente: `http://127.0.0.1:8000/auth/google/callback`
4. Haz clic en **Crear**.
5. Copia los valores de **ID de cliente** y **Secreto de cliente** que te mostrará en pantalla.

### Paso C: Autorizar tu Cuenta de Pruebas (Icono 4 👥)
*Dado que tu aplicación está en modo de prueba, Google bloqueará el inicio de sesión de cualquier cuenta que no agregues aquí.*
1. Haz clic en el **Icono 4 (Audiencia / Usuarios de prueba)**.
2. Haz clic en el botón **Agregar usuarios** (Add users).
3. Escribe tu dirección de correo de Gmail con la que vas a probar el login en tu computadora.
4. Haz clic en **Guardar** o **Agregar**.

---

## 3. Diferencias entre Desarrollo y Producción (Qué Cambiar)

Cuando decidas subir tu proyecto a producción en internet, el flujo de Google requiere cambios específicos debido a reglas de seguridad y dominios reales:

| Concepto | Desarrollo Local | Producción (Internet) |
| :--- | :--- | :--- |
| **Protocolo HTTP** | Permite `http://` normal | Exige obligatoriamente **`https://`** (SSL) |
| **URI de Redirección** | `http://127.0.0.1:8000/auth/google/callback` | `https://tuservidor.com/auth/google/callback` |
| **Origen de JavaScript** | `http://127.0.0.1:8000` | `https://tuservidor.com` |
| **Estado de Publicación** | **Prueba** (*Testing*) | **Producción** (*In production*) |
| **Usuarios permitidos** | Solo cuentas añadidas en "Usuarios de prueba" | Cualquier usuario con cuenta de Gmail |

### Pasos para pasar a Producción:
1. **Crear credenciales de producción**: Ve al **Icono 3 (Clientes)**, crea un nuevo cliente OAuth para producción, y define los URIs con tu dominio real usando `https://`.
2. **Publicar la aplicación**: Ve al **Icono 2 (Información de la marca)** y busca el botón **Publicar aplicación** (*Publish App*) para cambiar el estado de *Prueba* a *Producción*. Al hacer esto, ya no requerirás añadir manualmente a cada usuario en la sección de "Usuarios de prueba" para que pueda entrar.
3. **Actualizar el Servidor**: Actualiza las credenciales de producción en el archivo `.env` de tu servidor en la nube.

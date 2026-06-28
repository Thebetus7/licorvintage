# Configuración del Entorno y Archivos Services

**Ruta del archivo:** `docs/google_sso/03_env_setup.md`

Este documento detalla la configuración que debes realizar en el código de tu servidor Laravel para conectar y habilitar las credenciales obtenidas de Google.

---

## 1. Configurar el archivo `.env`

Abre tu archivo `.env` en la raíz del proyecto y agrega las siguientes variables de entorno.

### En Desarrollo Local:
```env
# Google OAuth 2.0 Credentials (Desarrollo)
GOOGLE_CLIENT_ID="TU_CLIENT_ID_LOCAL.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="TU_CLIENT_SECRET_LOCAL"
GOOGLE_REDIRECT_URI="http://127.0.0.1:8000/auth/google/callback"
```

### En Producción (Servidor en Internet):
Cuando subas tu web a producción, debes actualizar las variables en el panel de tu servidor con las credenciales de producción creadas para tu dominio:
```env
# Google OAuth 2.0 Credentials (Producción)
GOOGLE_CLIENT_ID="TU_CLIENT_ID_PRODUCCION.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="TU_CLIENT_SECRET_PRODUCCION"
GOOGLE_REDIRECT_URI="https://tuservidor.com/auth/google/callback"
```

> [!WARNING]
> Recuerda cambiar los valores de ejemplo por los valores reales. No subas estas credenciales a repositorios públicos como GitHub. Asegúrate de que en producción la URL de redirección empiece obligatoriamente con **`https://`**.

---

## 2. Configurar `config/services.php`

Ruta física del archivo: `config/services.php`

Para que Laravel Socialite reconozca estas variables de entorno de forma automática (tanto en desarrollo como en producción), agregamos la configuración del driver de Google al final del archivo:

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

---

## 3. Pruebas y Flujo en Local

Para que la redirección funcione en desarrollo local:
*   Asegúrate de levantar el servidor usando:
    ```bash
    php artisan serve
    ```
*   Accede a la aplicación a través de la dirección IP local de desarrollo: `http://127.0.0.1:8000` en tu navegador.
*   **Importante**: Google OAuth no reconocerá URLs genéricas como `localhost` a menos que las hayas añadido explícitamente en el listado de redireccionamiento autorizado de tus credenciales de la consola de Google.

---

## 4. Consideraciones para Servidores Privados (VPS, Redes Locales o Intranets)

Si vas a desplegar tu proyecto en un **servidor privado** (como un VPS propio de DigitalOcean, AWS, o una máquina física propia), debes tener en cuenta dos escenarios:

### Escenario A: Servidor Privado Conectado a Internet
*   **Sí, es exactamente la misma configuración**. 
*   **Requisito de Internet**: El servidor debe tener salida a internet para poder conectarse a las APIs de Google (`https://oauth2.googleapis.com`) y verificar el token.
*   **Requisito de SSL**: Google **exige** que la URL de producción use **HTTPS** obligatoriamente. No funcionará con `http://` para dominios externos o IPs públicas. Puedes usar servicios gratuitos como *Let's Encrypt* para obtener un certificado SSL.

### Escenario B: Servidor en Red Local Cerrada (Intranet sin Internet)
*   **No funcionará**: Si tu servidor está en una red aislada sin conexión a internet, **no podrás usar Google SSO**. 
*   **Por qué**: Durante el flujo de autenticación, tu servidor Laravel debe realizar una llamada interna a los servidores de Google para intercambiar el código por el perfil del usuario. Si no hay conexión de salida, el sistema dará un error de conexión (*connection timeout*).


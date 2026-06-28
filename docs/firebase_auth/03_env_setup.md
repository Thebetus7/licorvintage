# Configuración de Variables de Entorno y SDK

**Ruta del archivo:** `docs/firebase_auth/03_env_setup.md`

Este documento detalla los valores que debes configurar en tu archivo `.env` para que el servidor Laravel y el cliente Vue 3 interactúen con tu proyecto de Firebase.

---

## 1. Variables en el Archivo `.env`

Asegúrate de agregar estas variables al final de tu archivo `.env` en la raíz de tu proyecto. Los valores corresponden a la aplicación web registrada en el proyecto `licorvintage-tw-p2`:

```env
# Firebase Auth Credentials
FIREBASE_API_KEY="AIzaSyBGMEcs0hAMAulE4nGmS_cUHBJ7dB7Usjs"
FIREBASE_AUTH_DOMAIN="licorvintage-tw-p2.firebaseapp.com"
FIREBASE_PROJECT_ID="licorvintage-tw-p2"
FIREBASE_STORAGE_BUCKET="licorvintage-tw-p2.firebasestorage.app"
FIREBASE_MESSAGING_SENDER_ID="80441886881"
FIREBASE_APP_ID="1:80441886881:web:dc802c71e0b2818e0c88e0"
```

---

## 2. Inyección de Credenciales al Frontend

Para evitar la duplicación de credenciales y no exponer archivos de configuración de Vite separados, Laravel inyecta dinámicamente estas variables a la aplicación Vue 3 a través del middleware de Inertia en `app/Http/Middleware/HandleInertiaRequests.php`:

```php
'firebase_config' => [
    'apiKey' => config('services.firebase.api_key'),
    'authDomain' => config('services.firebase.auth_domain'),
    'projectId' => config('services.firebase.project_id'),
    'storageBucket' => config('services.firebase.storage_bucket'),
    'messagingSenderId' => config('services.firebase.messaging_sender_id'),
    'appId' => config('services.firebase.app_id'),
],
```

El helper `resources/js/firebase.js` lee la propiedad `firebase_config` compartida por Inertia y la utiliza para inicializar la instancia de autenticación en el cliente de manera transparente.

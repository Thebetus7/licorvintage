import { initializeApp, getApps, getApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';
import { usePage } from '@inertiajs/vue3';

let authInstance = null;

export function getFirebaseAuth() {
    if (authInstance) {
        return authInstance;
    }

    const page = usePage();
    const config = page.props.firebase_config;

    if (!config || !config.apiKey) {
        console.error('Firebase configuration is missing in Inertia props.');
        return null;
    }

    // Inicializar Firebase
    const app = getApps().length === 0 ? initializeApp(config) : getApp();
    authInstance = getAuth(app);
    return authInstance;
}

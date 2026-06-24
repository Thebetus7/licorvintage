import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

export const syncCsrfToken = (token) => {
    if (! token) {
        return;
    }

    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;

    const meta = document.head.querySelector('meta[name="csrf-token"]');

    if (meta) {
        meta.setAttribute('content', token);
    }
};

syncCsrfToken(document.head.querySelector('meta[name="csrf-token"]')?.content);

import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Laravel Echo + Pusher
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});

// --- DEBUG CONNECTION ---
window.Echo.connector.pusher.connection.bind('connecting', () => console.log('Connecting...'));
window.Echo.connector.pusher.connection.bind('connected', () => console.log('Connected'));
window.Echo.connector.pusher.connection.bind('error', (err) => console.log('Error', err));
window.Echo.connector.pusher.connection.bind('disconnected', () => console.log('Disconnected'));

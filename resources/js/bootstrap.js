/**
 * Kami akan memuat pustaka HTTP axios yang memungkinkan kita dengan mudah
 * mengirimkan permintaan ke backend Laravel. Pustaka ini secara otomatis
 * menangani pengiriman token CSRF sebagai header berdasarkan nilai cookie
 * token "XSRF".
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo mengekspos API yang ekspresif untuk berlangganan channel dan mendengarkan
 * event yang disiarkan oleh Laravel. Echo dan event broadcasting memungkinkan tim
 * Anda untuk dengan mudah membangun aplikasi web real-time yang andal.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

import _ from 'lodash';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window._ = _;
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

// Enable verbose Pusher logging so we can see connection + auth details
Pusher.logToConsole = true;

const reverbConfig = window.Laravel?.reverb || {};

const reverbKey    = reverbConfig.key || import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;
const reverbHost   = reverbConfig.host || import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST;
const reverbPort   = Number(reverbConfig.port || import.meta.env.VITE_REVERB_PORT || import.meta.env.VITE_PUSHER_PORT || 8080);
const reverbScheme = reverbConfig.scheme || import.meta.env.VITE_REVERB_SCHEME || import.meta.env.VITE_PUSHER_SCHEME || 'http';
const isTLS        = reverbScheme === 'https';

console.log('[bootstrap] Reverb config →', { reverbKey, reverbHost, reverbPort, reverbScheme, isTLS });

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: isTLS,
        // 'ws' is the ONLY valid WebSocket transport in Pusher-js (covers both ws:// and wss://)
        // TLS is handled by forceTLS above, NOT by the transport name
        enabledTransports: ['ws'],
        // Custom authorizer so axios carries session cookies + CSRF automatically
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name,
                })
            .then(res => callback(null, res.data))
            .catch(err => {
                console.error('[Echo Auth] Channel auth failed:', channel.name, err?.response?.status, err?.response?.data);
                callback(err);
            });
        },
    }),
});
}

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,

  wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8081),
  wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8081),

  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],

  authEndpoint: '/broadcasting/auth',
  withCredentials: true,
});

// Handle Reverb (Pusher-style) connection events
const socket = window.Echo.connector.pusher.connection;

socket.bind("connected", () => {
    console.log(import.meta.env.VITE_REVERB_HOST ?? window.location.hostname);
    console.log(Number(import.meta.env.VITE_REVERB_PORT ?? 8081))
    console.log("Connected to Reverb WebSocket server!");
});

socket.bind("disconnected", () => {
    console.warn("Disconnected from Reverb server.");
});

socket.bind("error", (err) => {
    console.error("WebSocket connection error:", err);
});

export default window.Echo;

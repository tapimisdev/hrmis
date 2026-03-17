import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,

  wsHost: window.location.hostname,
  wsPort: 443,
  wssPort: 443,

  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],

  authEndpoint: '/broadcasting/auth',
  withCredentials: true,
});

// Handle Reverb (Pusher-style) connection events
const socket = window.Echo.connector.pusher.connection;

socket.bind("connected", () => {
    console.log("Connected to Reverb WebSocket server!");
});

socket.bind("disconnected", () => {
    console.warn("Disconnected from Reverb server.");
});

socket.bind("error", (err) => {
    console.error("WebSocket connection error:", err);
});

export default window.Echo;

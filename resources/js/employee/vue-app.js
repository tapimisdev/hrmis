import { createApp } from 'vue';
import CheckInOutVue from './check-in-out/CheckInOutVue.vue';

// Mount CheckInOutVue if #app exists
export function mountVueApps() {
  const el = document.getElementById('app');
  if (el) {
    createApp(CheckInOutVue).mount(el);
  }
}
import './bootstrap';
import { startAutoSync } from './offline/sync';
import offlinePos from './offline/pos-component';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('offlinePos', offlinePos);
});

startAutoSync();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

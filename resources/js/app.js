import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';

const root = document.getElementById('app');

if (root) {
    createApp(App).mount(root);
} else {
    console.warn('[pharmacovigilance] #app not found');
}

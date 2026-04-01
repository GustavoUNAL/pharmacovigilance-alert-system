<script setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';
import LoginForm from './components/LoginForm.vue';
import PharmaDashboard from './components/PharmaDashboard.vue';

const checking = ref(true);
const isAuthenticated = ref(false);

onMounted(async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
    } catch {
        console.warn('[auth] sanctum csrf-cookie request failed');
    }

    try {
        await axios.get('/api/user');
        isAuthenticated.value = true;
    } catch {
        isAuthenticated.value = false;
    } finally {
        checking.value = false;
    }
});

function onLoginSuccess() {
    isAuthenticated.value = true;
}

function onLogout() {
    isAuthenticated.value = false;
}
</script>

<template>
    <div v-if="checking" class="pv-loading">Loading…</div>
    <LoginForm v-else-if="!isAuthenticated" @success="onLoginSuccess" />
    <PharmaDashboard v-else @logout="onLogout" />
</template>

<style scoped>
.pv-loading {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: system-ui, sans-serif;
    color: #64748b;
}
</style>

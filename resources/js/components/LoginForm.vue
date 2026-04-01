<script setup>
import axios from 'axios';
import { ref } from 'vue';

const emit = defineEmits(['success']);

const username = ref('');
const password = ref('');
const loading = ref(false);
const errorMessage = ref('');

async function submit() {
    errorMessage.value = '';
    loading.value = true;

    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.post('/api/login', {
            username: username.value,
            password: password.value,
        });
        emit('success');
    } catch (e) {
        const data = e.response?.data;
        if (data?.errors?.username?.[0]) {
            errorMessage.value = data.errors.username[0];
        } else if (typeof data?.message === 'string') {
            errorMessage.value = data.message;
        } else {
            errorMessage.value = 'Login failed.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="pv-login-page">
        <div class="pv-login-card">
            <header class="pv-login-header">Pharmacovigilance</header>
            <form class="pv-login-body" @submit.prevent="submit">
                <label class="pv-label">Username</label>
                <input
                    v-model="username"
                    class="pv-input"
                    type="text"
                    name="username"
                    autocomplete="username"
                />
                <label class="pv-label">Password</label>
                <input
                    v-model="password"
                    class="pv-input"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                />
                <p v-if="errorMessage" class="pv-error">{{ errorMessage }}</p>
                <button class="pv-btn" type="submit" :disabled="loading">
                    Login
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.pv-login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8e8e8;
    margin: 0;
    padding: 1rem;
    box-sizing: border-box;
}
.pv-login-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #d0d0d0;
}
.pv-login-header {
    background: #2c3e50;
    color: #fff;
    text-align: center;
    padding: 1.25rem 1rem;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.02em;
}
.pv-login-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.pv-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #34495e;
    margin-top: 0.5rem;
}
.pv-label:first-of-type {
    margin-top: 0;
}
.pv-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.65rem 0.75rem;
    border: 1px solid #bdc3c7;
    border-radius: 6px;
    font-size: 1rem;
    color: #2c3e50;
}
.pv-input:focus {
    outline: none;
    border-color: #2c3e50;
    box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.18);
}
.pv-error {
    color: #c0392b;
    font-size: 0.875rem;
    margin: 0.5rem 0 0;
}
.pv-btn {
    width: 100%;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: #27ae60;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
}
.pv-btn:hover:not(:disabled) {
    background: #219a52;
}
.pv-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>

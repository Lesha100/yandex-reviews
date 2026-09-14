<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const email = ref('test@example.com');
const password = ref('password');

const loading = ref(false);
const error = ref('');

const login = async () => {
    error.value = '';
    loading.value = true;

    try {
        await api.get('/sanctum/csrf-cookie');

        await api.post('/api/login', {
            email: email.value,
            password: password.value,
        });

        await api.get('/api/user');

        await router.push('/settings');
    } catch (e) {
        if (e.response?.status === 422) {
            error.value = 'Please check the entered data.';
        } else if (e.response?.status === 401) {
            error.value = 'Invalid email or password.';
        } else {
            error.value = 'Something went wrong. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <main class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md rounded-xl bg-white p-8 shadow">
            <h1 class="mb-2 text-2xl font-semibold text-gray-900">
                Yandex Reviews
            </h1>

            <p class="mb-6 text-sm text-gray-500">
                Sign in to continue
            </p>

            <form @submit.prevent="login" class="space-y-5">
                <div>
                    <label
                        for="email"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Email
                    </label>

                    <input
                        id="email"
                        v-model="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none focus:border-gray-500"
                    >
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-1 block text-sm font-medium text-gray-700"
                    >
                        Password
                    </label>

                    <input
                        id="password"
                        v-model="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none focus:border-gray-500"
                    >
                </div>

                <p
                    v-if="error"
                    class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600"
                >
                    {{ error }}
                </p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full rounded-lg bg-black px-4 py-2.5 font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ loading ? 'Signing in...' : 'Sign in' }}
                </button>
            </form>
        </div>
    </main>
</template>

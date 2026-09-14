<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const yandexUrl = ref('');
const organization = ref(null);

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const loggingOut = ref(false);

let pollingTimer = null;

const stopPolling = () => {
    if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
};

const handleUnauthorized = async () => {
    stopPolling();
    await router.push('/login');
};

const pollOrganization = async () => {
    try {
        const response = await api.get('/api/organizations');

        organization.value = response.data.data;

        const status = organization.value.parse_status;

        if (status === 'completed') {
            saving.value = false;
            stopPolling();
            return;
        }

        if (status === 'failed') {
            saving.value = false;
            stopPolling();

            error.value =
                organization.value.parse_error ||
                'Failed to parse data from Yandex Maps.';

            return;
        }

        saving.value = true;
    } catch (e) {
        stopPolling();
        saving.value = false;

        if (e.response?.status === 401) {
            await handleUnauthorized();
            return;
        }

        error.value = 'Failed to load organization status.';
    }
};

const startPolling = () => {
    stopPolling();

    pollingTimer = setInterval(pollOrganization, 1500);
};

const loadOrganization = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/api/organizations');

        organization.value = response.data.data;
        yandexUrl.value = response.data.data.yandex_url;

        const status = organization.value.parse_status;

        if (status === 'pending' || status === 'processing') {
            saving.value = true;
            startPolling();
        }
    } catch (e) {
        if (e.response?.status === 401) {
            await handleUnauthorized();
            return;
        }

        if (e.response?.status !== 404) {
            error.value = 'Failed to load organization settings.';
        }
    } finally {
        loading.value = false;
    }
};

const saveOrganization = async () => {
    stopPolling();

    error.value = '';
    saving.value = true;

    try {
        const response = await api.post('/api/organizations', {
            yandex_url: yandexUrl.value,
        });

        organization.value = response.data.data;
        yandexUrl.value = response.data.data.yandex_url;

        startPolling();
    } catch (e) {
        saving.value = false;

        if (e.response?.status === 422) {
            error.value =
                e.response.data?.message ||
                'Unable to parse this Yandex Maps URL.';
        } else if (e.response?.status === 401) {
            await handleUnauthorized();
        } else {
            error.value =
                'Failed to start Yandex Maps parsing.';
        }
    }
};

const logout = async () => {
    if (loggingOut.value) {
        return;
    }

    loggingOut.value = true;

    try {
        await api.post('/api/logout');
    } catch (e) {
        console.error('Logout failed:', e);
    } finally {
        stopPolling();
        await router.push('/login');
        loggingOut.value = false;
    }
};

onMounted(loadOrganization);

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <main class="min-h-screen bg-gray-100">
        <header class="border-b bg-white">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4"
            >
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        Yandex Reviews
                    </h1>

                    <p class="text-sm text-gray-500">
                        Organization settings
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="loggingOut"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="logout"
                >
                    {{ loggingOut ? 'Signing out...' : 'Sign out' }}
                </button>
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-4 py-8">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Yandex Maps organization
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Paste the URL of your organization on Yandex Maps.
                </p>

                <form
                    class="mt-6"
                    @submit.prevent="saveOrganization"
                >
                    <label
                        for="yandex-url"
                        class="mb-2 block text-sm font-medium text-gray-700"
                    >
                        Yandex Maps URL
                    </label>

                    <div class="flex gap-3">
                        <input
                            id="yandex-url"
                            v-model="yandexUrl"
                            type="url"
                            required
                            placeholder="https://yandex.ru/maps/org/..."
                            :disabled="saving"
                            class="min-w-0 flex-1 rounded-lg border border-gray-300 px-3 py-2.5 outline-none focus:border-gray-500 disabled:bg-gray-100"
                        >

                        <button
                            type="submit"
                            :disabled="saving || loading"
                            class="rounded-lg bg-black px-5 py-2.5 font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ saving ? 'Parsing...' : 'Save' }}
                        </button>
                    </div>

                    <div
                        v-if="saving && organization"
                        class="mt-4"
                    >
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-gray-600">
                                Parsing Yandex Maps...
                            </span>

                            <span class="font-medium text-gray-900">
                                {{ organization.parse_progress }}%
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-gray-200">
                            <div
                                class="h-full rounded-full bg-black transition-all duration-300"
                                :style="{
                                    width: `${organization.parse_progress}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </form>

                <div
                    v-if="error"
                    class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600"
                >
                    {{ error }}
                </div>
            </div>

            <div
                v-if="loading"
                class="mt-6 rounded-xl bg-white p-6 text-center text-sm text-gray-500 shadow-sm"
            >
                Loading organization...
            </div>

            <div
                v-else-if="organization"
                class="mt-6 rounded-xl bg-white p-6 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ organization.name || 'Organization' }}
                        </h2>

                        <p class="mt-1 break-all text-sm text-gray-500">
                            {{ organization.yandex_url }}
                        </p>
                    </div>

                    <div class="text-right">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ Number(organization.rating).toFixed(1) }}
                        </div>

                        <div class="text-sm text-gray-500">
                            Average rating
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ organization.ratings_count }}
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Ratings
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ organization.reviews_count }}
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Reviews
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ organization.yandex_id || '—' }}
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Yandex ID
                        </div>
                    </div>
                </div>

                <div
                    v-if="organization.last_parsed_at"
                    class="mt-6 text-sm text-gray-500"
                >
                    Last parsed:
                    {{ new Date(organization.last_parsed_at).toLocaleString() }}
                </div>

                <div class="mt-6">
                    <button
                        type="button"
                        :disabled="saving"
                        class="rounded-lg bg-gray-900 px-5 py-2.5 font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="router.push('/reviews')"
                    >
                        View reviews
                    </button>
                </div>
            </div>
        </div>
    </main>
</template>

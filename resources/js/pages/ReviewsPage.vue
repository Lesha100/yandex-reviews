<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const organization = ref(null);
const reviews = ref([]);

const loading = ref(true);
const error = ref('');

const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

const loadOrganization = async () => {
    const response = await api.get('/api/organizations');

    organization.value = response.data.data;
};

const loadReviews = async (page = 1) => {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.get('/api/reviews', {
            params: {
                page,
            },
        });

        reviews.value = response.data.data;
        currentPage.value = response.data.meta.current_page;
        lastPage.value = response.data.meta.last_page;
        total.value = response.data.meta.total;
    } catch (e) {
        if (e.response?.status === 401) {
            await router.push('/login');
            return;
        }

        error.value =
            e.response?.data?.message ||
            'Failed to load reviews. Please try again.';
    } finally {
        loading.value = false;
    }
};

const changePage = async (page) => {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    await loadReviews(page);

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};

const paginationPages = computed(() => {
    const pages = [];

    if (lastPage.value <= 7) {
        for (let page = 1; page <= lastPage.value; page++) {
            pages.push(page);
        }

        return pages;
    }

    pages.push(1);

    if (currentPage.value > 4) {
        pages.push('...');
    }

    const start = Math.max(2, currentPage.value - 1);
    const end = Math.min(lastPage.value - 1, currentPage.value + 1);

    for (let page = start; page <= end; page++) {
        pages.push(page);
    }

    if (currentPage.value < lastPage.value - 3) {
        pages.push('...');
    }

    pages.push(lastPage.value);

    return pages;
});

const formatDate = (date) => {
    if (!date) {
        return 'Date not specified';
    }

    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

onMounted(async () => {
    try {
        await loadOrganization();
        await loadReviews();
    } catch (e) {
        if (e.response?.status === 401) {
            await router.push('/login');
            return;
        }

        error.value =
            e.response?.data?.message ||
            'Failed to load reviews. Please try again.';

        loading.value = false;
    }
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
                        Organization reviews
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="router.push('/settings')"
                >
                    Settings
                </button>
            </div>
        </header>

        <div class="mx-auto max-w-5xl px-4 py-8">
            <div
                v-if="organization"
                class="rounded-xl bg-white p-6 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ organization.name }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ total }} reviews loaded from Yandex Maps
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
                            Reviews on Yandex
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4">
                        <div class="text-2xl font-semibold text-gray-900">
                            {{ currentPage }}
                        </div>

                        <div class="mt-1 text-sm text-gray-500">
                            Page of {{ lastPage }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="loading"
                class="mt-6 rounded-xl bg-white p-10 text-center text-sm text-gray-500 shadow-sm"
            >
                Loading reviews...
            </div>

            <div
                v-else-if="error"
                class="mt-6 rounded-xl bg-red-50 p-6 text-sm text-red-600"
            >
                {{ error }}
            </div>

            <template v-else>
                <div class="mt-6 space-y-4">
                    <article
                        v-for="review in reviews"
                        :key="review.id"
                        class="rounded-xl bg-white p-6 shadow-sm"
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    {{ review.author || 'Anonymous' }}
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    {{ formatDate(review.published_at) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <span
                                    v-for="star in 5"
                                    :key="star"
                                    class="text-lg"
                                    :class="
                                        star <= review.rating
                                            ? 'text-yellow-400'
                                            : 'text-gray-300'
                                    "
                                >
                                    ★
                                </span>

                                <span
                                    class="ml-1 text-sm font-medium text-gray-600"
                                >
                                    {{ review.rating }}/5
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="review.text"
                            class="mt-4 whitespace-pre-line text-sm leading-6 text-gray-700"
                        >
                            {{ review.text }}
                        </p>

                        <p
                            v-else
                            class="mt-4 text-sm italic text-gray-400"
                        >
                            No review text.
                        </p>
                    </article>
                </div>

                <div
                    v-if="lastPage > 1"
                    class="mt-8 flex flex-wrap items-center justify-center gap-2"
                >
                    <button
                        type="button"
                        :disabled="currentPage === 1"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="changePage(currentPage - 1)"
                    >
                        Previous
                    </button>

                    <template
                        v-for="(page, index) in paginationPages"
                        :key="`${page}-${index}`"
                    >
                        <span
                            v-if="page === '...'"
                            class="px-2 text-gray-400"
                        >
                            ...
                        </span>

                        <button
                            v-else
                            type="button"
                            class="min-w-10 rounded-lg border px-3 py-2 text-sm font-medium"
                            :class="
                                page === currentPage
                                    ? 'border-gray-900 bg-gray-900 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                            "
                            @click="changePage(page)"
                        >
                            {{ page }}
                        </button>
                    </template>

                    <button
                        type="button"
                        :disabled="currentPage === lastPage"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="changePage(currentPage + 1)"
                    >
                        Next
                    </button>
                </div>
            </template>
        </div>
    </main>
</template>

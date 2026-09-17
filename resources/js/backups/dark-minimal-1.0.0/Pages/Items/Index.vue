<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ItemCard from '@/Components/ItemCard.vue';

const props = defineProps({
    items: Object,
    categories: Array,
    subcategories: Array,
    tiers: Array,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const subcategory = ref(props.filters.subcategory ?? '');
const tier = ref(props.filters.tier ?? '');
const sort = ref(props.filters.sort ?? 'name');
const direction = ref(props.filters.direction ?? 'asc');

const filteredSubcategories = () => {
    if (!category.value) return props.subcategories;
    return props.subcategories.filter((s) => s.category_slug === category.value);
};

const applyFilters = () => {
    router.get(
        '/items',
        {
            search: search.value || undefined,
            category: category.value || undefined,
            subcategory: subcategory.value || undefined,
            tier: tier.value || undefined,
            sort: sort.value,
            direction: direction.value,
        },
        {
            preserveState: true,
            replace: true,
            onSuccess: () => saveCurrentQuery(), // <-- ajout ici
        }
    );
};

// Debounce maison (pas de dépendance externe)
function debounce(fn, delay) {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
}

const debouncedSearch = debounce(applyFilters, 400);

watch(search, () => debouncedSearch());
watch([category, subcategory, tier, sort, direction], () => applyFilters());

watch(category, () => {
    subcategory.value = '';
});

const toggleDirection = () => {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc';
};

// Sauvegarde la query string actuelle pour permettre un retour fidèle depuis Show.vue
function saveCurrentQuery() {
    const params = new URLSearchParams(window.location.search)
    const queryString = params.toString()
    if (queryString) {
        sessionStorage.setItem('items_index_query', queryString)
    } else {
        sessionStorage.removeItem('items_index_query')
    }
}

// Sauvegarde immédiate au montage (capte l'état initial de l'URL)
onMounted(() => {
    saveCurrentQuery()
})
</script>

<template>
    <AppLayout title="Items">
        <div class="mx-auto max-w-7xl px-4 py-8">
            <h1 class="mb-6 text-2xl font-bold text-white">Base d'items</h1>

            <!-- Filtres -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un item..."
                    class="rounded-md border-gray-700 bg-gray-900 text-white placeholder-gray-500"
                />

                <select v-model="category" class="rounded-md border-gray-700 bg-gray-900 text-white">
                    <option value="">Toutes catégories</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>

                <select v-model="subcategory" class="rounded-md border-gray-700 bg-gray-900 text-white">
                    <option value="">Toutes sous-catégories</option>
                    <option
                        v-for="sub in filteredSubcategories()"
                        :key="sub.subcategory_slug"
                        :value="sub.subcategory_slug"
                    >
                        {{ sub.item_type_name }}
                    </option>
                </select>

                <select v-model="tier" class="rounded-md border-gray-700 bg-gray-900 text-white">
                    <option value="">Tous les tiers</option>
                    <option v-for="t in tiers" :key="t" :value="t">
                        {{ t }}
                    </option>
                </select>

                <div class="flex gap-2">
                    <select v-model="sort" class="w-full rounded-md border-gray-700 bg-gray-900 text-white">
                        <option value="name">Nom</option>
                        <option value="tier_type_name">Tier</option>
                        <option value="item_type_name">Type</option>
                        <option value="created_at">Ajout</option>
                    </select>

                    <button
                        @click="toggleDirection"
                        type="button"
                        class="rounded-md border border-gray-700 bg-gray-900 px-3 text-white"
                        :title="direction === 'asc' ? 'Croissant' : 'Décroissant'"
                    >
                        {{ direction === 'asc' ? '↑' : '↓' }}
                    </button>
                </div>
            </div>

            <!-- Grille -->
            <div
                v-if="items.data.length"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6"
            >
                <ItemCard v-for="item in items.data" :key="item.hash" :item="item" />
            </div>

            <p v-else class="text-center text-gray-400">
                Aucun item ne correspond à ces critères.
            </p>

            <!-- Pagination -->
            <div v-if="items.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1">
                <button
                    v-for="(link, index) in items.links"
                    :key="index"
                    :disabled="!link.url"
                    @click="link.url && router.get(link.url, {}, { preserveState: true })"
                    v-html="link.label"
                    class="rounded-md px-3 py-1 text-sm"
                    :class="[
                        link.active ? 'bg-blue-600 text-white' : 'bg-gray-800 text-gray-300 hover:bg-gray-700',
                        !link.url && 'cursor-not-allowed opacity-50',
                    ]"
                />
            </div>
        </div>
    </AppLayout>
</template>
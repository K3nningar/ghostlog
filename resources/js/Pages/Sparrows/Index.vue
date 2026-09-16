<template>
    <AppLayout :title="$t('sparrows.title')">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6 text-white">{{ $t('sparrows.title') }}</h1>

            <!-- Filtres -->
            <div class="flex flex-wrap gap-4 mb-6 bg-gray-800 p-4 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.search_label') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('sparrows.search_placeholder')"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    />
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.rarity_label') }}</label>
                    <select
                        v-model="tierFilter"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    >
                        <option value="">{{ $t('items.rarity_all') }}</option>
                        <option v-for="tier in availableTiers" :key="tier" :value="tier">
                            {{ tier }}
                        </option>
                    </select>
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.confidentiality_label') }}</label>
                    <select
                        v-model="confidentialFilter"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    >
                        <option value="">{{ $t('items.confidentiality_all') }}</option>
                        <option value="classified">{{ $t('items.classified_only') }}</option>
                        <option value="public">{{ $t('items.public_only') }}</option>
                    </select>
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.sort_label') }}</label>
                    <select
                        v-model="sortBy"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        @click="resetFilters"
                        class="text-sm text-gray-400 hover:text-white underline px-2 py-2"
                    >
                        {{ $t('items.reset') }}
                    </button>
                </div>
            </div>

            <p class="text-sm text-gray-400 mb-4">
                {{ filteredSparrows.length }} passereau{{ filteredSparrows.length > 1 ? 'x' : '' }} trouvé{{ filteredSparrows.length > 1 ? 's' : '' }}
            </p>

            <!-- Grille unique -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-12 gap-6">
                <div
                    v-for="sparrow in filteredSparrows"
                    :key="sparrow.id"
                    class="relative bg-gray-800 rounded-lg p-3 hover:bg-gray-700 transition cursor-pointer border"
                    :class="tierBorderClass(sparrow.tier_type)"
                    @click="openSparrowDetail(sparrow)"
                >
                    <!-- Badges -->
                    <div class="absolute top-1 left-1 flex flex-col gap-1 z-10">
                        <span
                            v-if="sparrow.subcategory_slug === 'secret'"
                            class="text-[10px] bg-purple-700 text-white px-1.5 py-0.5 rounded font-semibold"
                        >
                            {{ $t('items.secret_item') }}
                        </span>
                        <span
                            v-if="sparrow.subcategory_slug === 'censored'"
                            class="text-[10px] bg-red-700 text-white px-1.5 py-0.5 rounded font-semibold"
                        >
                            {{ $t('items.censored_item') }}
                        </span>
                        <span
                            v-if="sparrow.subcategory_slug === 'classified'"
                            class="text-[10px] bg-orange-700 text-white px-1.5 py-0.5 rounded font-semibold"
                        >
                            {{ $t('items.classified_item') }}
                        </span>
                    </div>

                    <img
                        v-if="sparrow.archive_icon_path"
                        :src="sparrow.archive_icon_path"
                        :alt="sparrow.name"
                        class="w-full aspect-square object-cover rounded mb-2"
                    />
                    <div
                        v-else
                        class="w-full aspect-square bg-gray-900 rounded mb-2 flex items-center justify-center text-gray-500 text-xs"
                    >
                        Pas d'icône
                    </div>

                    <p class="text-sm text-white font-semibold truncate">{{ sparrow.name }}</p>
                    <p class="text-xs text-gray-400">{{ sparrow.tier_type_name }}</p>
                </div>
            </div>

            <p v-if="filteredSparrows.length === 0" class="text-center text-gray-500 py-12">
                Aucun passereau ne correspond à ces critères.
            </p>

            <!-- Drawer de détails -->
            <SparrowDetailDrawer
                :sparrow="selectedSparrow"
                :open="isDrawerOpen"
                @close="closeSparrowDetail"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SparrowDetailDrawer from '@/Components/SparrowDetailDrawer.vue';

const props = defineProps({
    sparrows: {
        type: Array,
        required: true,
    },
});

const search = ref('');
const tierFilter = ref('');
const confidentialFilter = ref('');
const cdnFilter = ref('');
const sortBy = ref('default'); // valeur par défaut = ordre du backend

const availableTiers = computed(() => {
    const tiers = new Set(props.sparrows.map((s) => s.tier_type_name).filter(Boolean));
    return Array.from(tiers);
});

const sortOptions = [
    { value: 'default', label: 'Par défaut' },
    { value: 'tier-desc', label: 'Tier (décroissant)' },
    { value: 'tier-asc', label: 'Tier (croissant)' },
    { value: 'name-asc', label: 'Nom (A → Z)' },
    { value: 'name-desc', label: 'Nom (Z → A)' },
    { value: 'hash-asc', label: 'Hash (croissant)' },
    { value: 'hash-desc', label: 'Hash (décroissant)' },
];

const filteredSparrows = computed(() => {
    const filtered = props.sparrows.filter((sparrow) => {
        const matchesSearch =
            search.value === '' ||
            sparrow.name.toLowerCase().includes(search.value.toLowerCase());

        const matchesTier =
            tierFilter.value === '' || sparrow.tier_type_name === tierFilter.value;

        const matchesConfidential =
            confidentialFilter.value === '' ||
            (confidentialFilter.value === 'classified' &&
                ['classified', 'censored', 'secret'].includes(sparrow.subcategory_slug)) ||
            (confidentialFilter.value === 'public' &&
                !['classified', 'censored', 'secret'].includes(sparrow.subcategory_slug));

        const matchesCdn =
            cdnFilter.value === '' ||
            (cdnFilter.value === 'available' && sparrow.icon_downloaded) ||
            (cdnFilter.value === 'unavailable' && !sparrow.icon_downloaded);

        return matchesSearch && matchesTier && matchesConfidential && matchesCdn;
    });

    // 'default' = on ne touche pas à l'ordre reçu du backend
    if (sortBy.value === 'default') {
        return filtered;
    }

    const sorted = [...filtered];

    switch (sortBy.value) {
        case 'tier-desc':
            sorted.sort(
                (a, b) =>
                    b.tier_type - a.tier_type ||
                    (a.name ?? '').localeCompare(b.name ?? '', 'fr', { sensitivity: 'base', numeric: true }),
            );
            break;
        case 'tier-asc':
            sorted.sort(
                (a, b) =>
                    a.tier_type - b.tier_type ||
                    (a.name ?? '').localeCompare(b.name ?? '', 'fr', { sensitivity: 'base', numeric: true }),
            );
            break;
        case 'name-asc':
            sorted.sort((a, b) =>
                (a.name ?? '').localeCompare(b.name ?? '', 'fr', { sensitivity: 'base', numeric: true }),
            );
            break;
        case 'name-desc':
            sorted.sort((a, b) =>
                (b.name ?? '').localeCompare(a.name ?? '', 'fr', { sensitivity: 'base', numeric: true }),
            );
            break;
        case 'hash-asc':
            sorted.sort((a, b) => (a.hash ?? 0) - (b.hash ?? 0));
            break;
        case 'hash-desc':
            sorted.sort((a, b) => (b.hash ?? 0) - (a.hash ?? 0));
            break;
    }

    return sorted;
});

function resetFilters() {
    search.value = '';
    tierFilter.value = '';
    confidentialFilter.value = '';
    cdnFilter.value = '';
}

function tierBorderClass(tierType) {
    switch (tierType) {
        case 6: // Exotique
            return 'border-yellow-500';
        case 5: // Légendaire
            return 'border-purple-500';
        case 4: // Rare
            return 'border-blue-500';
        case 3: // Peu commun
            return 'border-green-500';
        default:
            return 'border-gray-600';
    }
}

const selectedSparrow = ref(null);
const isDrawerOpen = ref(false);

function openSparrowDetail(sparrow) {
    selectedSparrow.value = sparrow;
    isDrawerOpen.value = true;
}

function closeSparrowDetail() {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedSparrow.value = null;
    }, 200);
}
</script>

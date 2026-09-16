<template>
    <AppLayout :title="$t('emblems.title')">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6 text-white">{{ $t('emblems.title') }}</h1>

            <div class="flex flex-wrap gap-4 mb-6 bg-gray-800 p-4 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.search_label') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('emblems.search_placeholder')"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    />
                </div>
                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.rarity_label') }}</label>
                    <select v-model="tierFilter" class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none">
                        <option value="">{{ $t('items.rarity_all') }}</option>
                        <option v-for="tier in availableTiers" :key="tier" :value="tier">{{ tier }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.confidentiality_label') }}</label>
                    <select v-model="confidentialFilter" class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none">
                        <option value="">{{ $t('items.confidentiality_all') }}</option>
                        <option value="classified">{{ $t('items.classified_only') }}</option>
                        <option value="public">{{ $t('items.public_only') }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.sort_label') }}</label>
                    <select v-model="sortBy" class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none">
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="resetFilters" class="text-sm text-gray-400 hover:text-white underline px-2 py-2">
                        {{ $t('items.reset') }}
                    </button>
                </div>
            </div>

            <p class="text-sm text-gray-400 mb-4">
                {{ $t('emblems.results_count', { count: filteredEmblems.length }) }}
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-12 gap-4">
                <div
                    v-for="emblem in filteredEmblems"
                    :key="emblem.id"
                    class="relative bg-gray-800 rounded-lg p-3 hover:bg-gray-700 transition cursor-pointer border"
                    :class="tierBorderClass(emblem.tier_type)"
                    @click="openEmblemDetail(emblem)"
                >
                    <div class="absolute top-1 left-1 flex flex-col gap-1 z-10">
                        <span v-if="emblem.subcategory_slug === 'secret'" class="text-[10px] bg-purple-700 text-white px-1.5 py-0.5 rounded font-semibold">
                            {{ $t('items.secret_item') }}
                        </span>
                        <span v-if="emblem.subcategory_slug === 'censored'" class="text-[10px] bg-red-700 text-white px-1.5 py-0.5 rounded font-semibold">
                            {{ $t('items.censored_item') }}
                        </span>
                        <span v-if="emblem.subcategory_slug === 'classified'" class="text-[10px] bg-orange-700 text-white px-1.5 py-0.5 rounded font-semibold">
                            {{ $t('items.classified_item') }}
                        </span>
                    </div>
                    <img
                        v-if="emblem.archive_icon_path"
                        :src="emblem.archive_icon_path"
                        :alt="emblem.name"
                        class="w-full aspect-square object-cover rounded mb-2"
                    />
                    <div v-else class="w-full aspect-square bg-gray-900 rounded mb-2 flex items-center justify-center text-gray-500 text-xs">
                        {{ $t('drawer.no_icon') }}
                    </div>
                    <p class="text-sm text-white font-semibold truncate">{{ emblem.name }}</p>
                    <p class="text-xs text-gray-400">{{ emblem.tier_type_name }}</p>
                </div>
            </div>

            <p v-if="filteredEmblems.length === 0" class="text-center text-gray-500 py-12">
                {{ $t('emblems.empty_state') }}
            </p>

            <EmblemDetailDrawer
                :emblem="selectedEmblem"
                :open="isDrawerOpen"
                @close="closeEmblemDetail"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmblemDetailDrawer from '@/Components/EmblemDetailDrawer.vue';

const props = defineProps({
    emblems: {
        type: Array,
        required: true,
    },
});

const search = ref('');
const tierFilter = ref('');
const confidentialFilter = ref('');
const sortBy = ref('default');

const availableTiers = computed(() => Array.from(new Set(props.emblems.map((emblem) => emblem.tier_type_name).filter(Boolean))));
const sortOptions = [
    { value: 'default', label: 'Par défaut' },
    { value: 'tier-desc', label: 'Tier (décroissant)' },
    { value: 'tier-asc', label: 'Tier (croissant)' },
    { value: 'name-asc', label: 'Nom (A → Z)' },
    { value: 'name-desc', label: 'Nom (Z → A)' },
    { value: 'hash-asc', label: 'Hash (croissant)' },
    { value: 'hash-desc', label: 'Hash (décroissant)' },
];

const filteredEmblems = computed(() => {
    const filtered = props.emblems.filter((emblem) => {
        const matchesSearch = search.value === '' || (emblem.name ?? '').toLowerCase().includes(search.value.toLowerCase());
        const matchesTier = tierFilter.value === '' || emblem.tier_type_name === tierFilter.value;
        const matchesConfidential = confidentialFilter.value === '' ||
            (confidentialFilter.value === 'classified' && ['classified', 'censored', 'secret'].includes(emblem.subcategory_slug)) ||
            (confidentialFilter.value === 'public' && !['classified', 'censored', 'secret'].includes(emblem.subcategory_slug));
        return matchesSearch && matchesTier && matchesConfidential;
    });

    if (sortBy.value === 'default') return filtered;

    const sorted = [...filtered];
    const nameCompare = (a, b) => (a.name ?? '').localeCompare(b.name ?? '', 'fr', { sensitivity: 'base', numeric: true });
    switch (sortBy.value) {
        case 'tier-desc': sorted.sort((a, b) => b.tier_type - a.tier_type || nameCompare(a, b)); break;
        case 'tier-asc': sorted.sort((a, b) => a.tier_type - b.tier_type || nameCompare(a, b)); break;
        case 'name-asc': sorted.sort(nameCompare); break;
        case 'name-desc': sorted.sort((a, b) => nameCompare(b, a)); break;
        case 'hash-asc': sorted.sort((a, b) => (a.hash ?? 0) - (b.hash ?? 0)); break;
        case 'hash-desc': sorted.sort((a, b) => (b.hash ?? 0) - (a.hash ?? 0)); break;
    }
    return sorted;
});

function resetFilters() {
    search.value = '';
    tierFilter.value = '';
    confidentialFilter.value = '';
    sortBy.value = 'default';
}

function tierBorderClass(tierType) {
    switch (tierType) {
        case 6: return 'border-yellow-500';
        case 5: return 'border-purple-500';
        case 4: return 'border-blue-500';
        case 3: return 'border-green-500';
        default: return 'border-gray-600';
    }
}

const selectedEmblem = ref(null);
const isDrawerOpen = ref(false);

function openEmblemDetail(emblem) {
    selectedEmblem.value = emblem;
    isDrawerOpen.value = true;
}

function closeEmblemDetail() {
    isDrawerOpen.value = false;
    setTimeout(() => { selectedEmblem.value = null; }, 200);
}
</script>

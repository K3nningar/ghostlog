<template>
    <AppLayout :title="$t('sparrows.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-[var(--dm-accent)]">{{ $t('layout.archive_index') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('sparrows.title') }}</h1>
                </div>
                <span class="text-xs text-white/40">{{ $t('layout.records', { count: filteredSparrows.length }) }}</span>
            </div>

            <!-- Filtres -->
            <div class="mb-6 flex flex-wrap gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.search_label') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('sparrows.search_placeholder')"
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-text-sky-300"
                    />
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('items.rarity_label') }}</label>
                    <select
                        v-model="tierFilter"
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-text-sky-300"
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
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-text-sky-300"
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
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-text-sky-300"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        @click="resetFilters"
                        class="px-2 py-2 text-sm text-white/50 underline transition hover:text-white"
                    >
                        {{ $t('items.reset') }}
                    </button>
                </div>
            </div>

            <p class="text-sm text-gray-400 mb-4">
                {{ filteredSparrows.length }} passereau{{ filteredSparrows.length > 1 ? 'x' : '' }} trouvé{{ filteredSparrows.length > 1 ? 's' : '' }}
            </p>

            <!-- Grille unique -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                <div
                    v-for="sparrow in filteredSparrows"
                    :key="sparrow.id"
                    class="group relative cursor-pointer rounded-xl border-2 bg-white/[0.03] p-3 transition hover:-translate-y-1 hover:bg-white/[0.06]"
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
                        class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100"
                    />
                    <div
                        v-else
                        class="w-full aspect-square bg-gray-900 rounded mb-2 flex items-center justify-center text-gray-500 text-xs"
                    >
                        Pas d'icône
                    </div>

                    <p class="truncate text-sm font-semibold text-white">{{ sparrow.name }}</p>
                    <p class="text-xs text-white/40">{{ sparrow.tier_type_name }}</p>
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
    switch (String(tierType).toLowerCase()) {
        case '6':
        case 'exotique':
            return 'border-yellow-500';
        case '5':
        case 'légendaire':
        case 'legendary':
            return 'border-purple-500';
        case '4':
        case 'rare':
            return 'border-blue-500';
        case '3':
        case 'peu commun':
        case 'uncommon':
            return 'border-green-500';
        default:
            return 'border-white';
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

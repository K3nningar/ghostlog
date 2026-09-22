<template>
    <AppLayout :title="$t('ships.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('ships.title') }}</h1>
                </div>
                <span class="text-xs text-white/40">{{ $t('layout.records', { count: totalCount }) }}</span>
            </div>

            <div class="mb-6 flex flex-wrap gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="min-w-[200px] flex-1">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.search_label') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('ships.search_placeholder')"
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"
                    />
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.rarity_label') }}</label>
                    <select v-model="tierFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="">{{ $t('items.rarity_all') }}</option>
                        <option v-for="tier in availableTiers" :key="tier" :value="tier">{{ tier }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.confidentiality_label') }}</label>
                    <select v-model="confidentialFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="">{{ $t('items.confidentiality_all') }}</option>
                        <option value="classified">{{ $t('items.classified_only') }}</option>
                        <option value="public">{{ $t('items.public_only') }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.sort_label') }}</label>
                    <select v-model="sortBy" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <button @click="resetFilters" class="self-end px-2 py-2 text-sm text-white/50 underline transition hover:text-white">{{ $t('items.reset') }}</button>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                <div
                    v-for="ship in ships.data"
                    :key="ship.id"
                    class="group relative cursor-pointer rounded-xl border-2 bg-white/[0.03] p-3 transition hover:-translate-y-1 hover:bg-white/[0.06]"
                    :class="tierBorderClass(ship.tier_type_name)"
                    @click="openShipDetail(ship)"
                >
                    <div class="absolute left-1 top-1 z-10 flex flex-col gap-1">
                        <span v-if="ship.subcategory_slug === 'secret'" class="rounded bg-purple-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.secret_item') }}</span>
                        <span v-if="ship.subcategory_slug === 'censored'" class="rounded bg-red-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.censored_item') }}</span>
                        <span v-if="ship.subcategory_slug === 'classified'" class="rounded bg-orange-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.classified_item') }}</span>
                        <span v-if="ship.subcategory_slug === 'beta'" class="rounded bg-blue-500/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.beta_item') }}</span>
                        <span v-if="ship.subcategory_slug === 'replaced'" class="rounded bg-gray-500/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.replaced_item') }}</span>
                    </div>
                    <img
                        v-if="ship.archive_icon_path"
                        :src="ship.archive_icon_path"
                        :alt="ship.name"
                        loading="lazy"
                        class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100"
                    />
                    <div v-else class="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-gray-900 text-xs text-gray-500">{{ $t('drawer.no_icon') }}</div>
                    <p class="truncate text-sm font-semibold text-white">{{ ship.name }}</p>
                    <p class="text-xs text-white/40">{{ ship.tier_type_name }}</p>
                </div>
            </div>

            <p v-if="ships.data.length === 0" class="py-12 text-center text-white/40">{{ $t('ships.empty_state') }}</p>

            <nav v-if="ships.links && ships.links.length > 3" class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
                <template v-for="(link, index) in ships.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="cursor-not-allowed rounded border border-white/5 px-3 py-1 text-xs text-white/30"
                        v-html="link.label"
                    />
                    <button
                        v-else
                        @click="goToPage(link.url)"
                        :class="[
                            'rounded border px-3 py-1 text-xs transition',
                            link.active
                                ? 'border-sky-400 bg-sky-500/20 text-white'
                                : 'border-white/10 text-white/70 hover:border-white/30 hover:text-white',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>

            <ShipDetailDrawer :ship="selectedShip" :open="isDrawerOpen" @close="closeShipDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShipDetailDrawer from '@/Components/ShipDetailDrawer.vue';

const props = defineProps({
    ships: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const tierFilter = ref(props.filters.tier ?? '');
const confidentialFilter = ref(props.filters.confidential ?? '');
const sortBy = ref(props.filters.sort ?? 'default');

const selectedShip = ref(null);
const isDrawerOpen = ref(false);

const sortOptions = [
    { value: 'default', label: 'Par défaut' },
    { value: 'tier-desc', label: 'Tier (décroissant)' },
    { value: 'tier-asc', label: 'Tier (croissant)' },
    { value: 'name-asc', label: 'Nom (A → Z)' },
    { value: 'name-desc', label: 'Nom (Z → A)' },
    { value: 'hash-asc', label: 'Hash (croissant)' },
    { value: 'hash-desc', label: 'Hash (décroissant)' },
];

const totalCount = computed(() => props.ships?.meta?.total ?? props.ships?.total ?? props.ships?.data?.length ?? 0);

const availableTiers = computed(() =>
    Array.from(new Set((props.ships?.data ?? []).map((ship) => ship.tier_type_name).filter(Boolean)))
);

const tierBorderClass = (tier) => {
    switch (String(tier).toLowerCase()) {
        case '6': case 'exotique': return 'border-yellow-500';
        case '5': case 'légendaire': case 'legendary': return 'border-purple-500';
        case '4': case 'rare': return 'border-blue-500';
        case '3': case 'peu commun': case 'uncommon': return 'border-green-500';
        default: return 'border-white';
    }
};

let searchTimer = null;
watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => sendFilters(), 300);
});
watch([tierFilter, confidentialFilter, sortBy], () => sendFilters());

function sendFilters() {
    router.get(
        '/ships',
        {
            search: search.value || undefined,
            tier: tierFilter.value || undefined,
            confidential: confidentialFilter.value || undefined,
            sort: sortBy.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function goToPage(url) {
    if (!url) return;
    router.visit(url, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    search.value = '';
    tierFilter.value = '';
    confidentialFilter.value = '';
    sortBy.value = 'default';
    if (searchTimer) { clearTimeout(searchTimer); searchTimer = null; }
    sendFilters();
}

function openShipDetail(ship) { selectedShip.value = ship; isDrawerOpen.value = true; }
function closeShipDetail() { isDrawerOpen.value = false; setTimeout(() => { selectedShip.value = null; }, 200); }

onMounted(() => {
    const f = usePage().props.filters ?? {};
    search.value = f.search ?? '';
    tierFilter.value = f.tier ?? '';
    confidentialFilter.value = f.confidential ?? '';
    sortBy.value = f.sort ?? 'default';
});
</script>

<template>
    <AppLayout :title="$t('emblems.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('emblems.title') }}</h1>
                </div>
                <span class="text-xs text-white/40">{{ $t('layout.records', { count: totalCount }) }}</span>
            </div>

            <div class="mb-6 flex flex-wrap gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="min-w-[200px] flex-1">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.search_label') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('emblems.search_placeholder')"
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"
                    />
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.rarity_label') }}</label>
                    <select v-model="tierFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="">{{ $t('items.rarity_all') }}</option>
                        <option v-for="tier in availableTiers" :key="tier.tier_type" :value="tier.tier_type_name" class="bg-black text-white" :style="{ backgroundColor: tierBgColor(tier.tier_type) }">{{ tier.tier_type_name }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.confidentiality_label') }}</label>
                    <select v-model="confidentialFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="" class="bg-black text-white">{{ $t('items.confidentiality_all') }}</option>
                        <option value="classified" class="bg-black text-white">{{ $t('items.classified_only') }}</option>
                        <option value="public" class="bg-black text-white">{{ $t('items.public_only') }}</option>
                    </select>
                </div>
                <div class="min-w-[180px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.sort_label') }}</label>
                    <select v-model="sortBy" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value" class="bg-black text-white">{{ option.label }}</option>
                    </select>
                </div>
                <div class="min-w-[120px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('items.per_page_label') }}</label>
                    <select v-model.number="perPage" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option v-for="size in perPageOptions" :key="size" :value="size" class="bg-black text-white">{{ size }}</option>
                    </select>
                </div>
                <button @click="resetFilters" class="self-end px-2 py-2 text-sm text-white/50 underline transition hover:text-white">{{ $t('items.reset') }}</button>
            </div>

            <nav v-if="emblems.links && emblems.links.length > 3" class="mb-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
                <template v-for="(link, index) in emblems.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="cursor-not-allowed rounded border border-white/5 px-3 py-1 text-xs text-white/30"
                        v-html="linkLabel(link.label)"
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
                        v-html="linkLabel(link.label)"
                    />
                </template>
            </nav>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                <div
                    v-for="emblem in emblems.data"
                    :key="emblem.id"
                    class="group relative cursor-pointer rounded-xl border-2 bg-white/[0.03] p-3 transition hover:-translate-y-1 hover:bg-white/[0.06]"
                    :class="tierBorderClass(emblem.tier_type)"
                    @click="openEmblemDetail(emblem)"
                >
                    <div class="absolute left-1 top-1 z-10 flex flex-col gap-1">
                        <span v-if="emblem.subcategory_slug === 'secret'" class="rounded bg-purple-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.secret_item') }}</span>
                        <span v-if="emblem.subcategory_slug === 'censored'" class="rounded bg-red-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.censored_item') }}</span>
                        <span v-if="emblem.subcategory_slug === 'classified'" class="rounded bg-orange-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.classified_item') }}</span>
                        <span v-if="emblem.subcategory_slug === 'beta'" class="rounded bg-blue-500/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.beta_item') }}</span>
                        <span v-if="emblem.subcategory_slug === 'replaced'" class="rounded bg-gray-500/90 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.replaced_item') }}</span>
                    </div>
                    <img
                        v-if="emblem.archive_icon_path"
                        :src="emblem.archive_icon_path"
                        :alt="emblem.name"
                        loading="lazy"
                        class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100"
                    />
                    <div v-else class="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-gray-900 text-xs text-gray-500">{{ $t('drawer.no_icon') }}</div>
                    <p class="truncate text-sm font-semibold text-white">{{ emblem.name }}</p>
                    <p class="text-xs text-white/40">{{ emblem.tier_type_name }}</p>
                </div>
            </div>

            <p v-if="emblems.data.length === 0" class="py-12 text-center text-white/40">{{ $t('emblems.empty_state') }}</p>

            <nav v-if="emblems.links && emblems.links.length > 3" class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
                <template v-for="(link, index) in emblems.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="cursor-not-allowed rounded border border-white/5 px-3 py-1 text-xs text-white/30"
                        v-html="linkLabel(link.label)"
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
                        v-html="linkLabel(link.label)"
                    />
                </template>
            </nav>

            <div class="mt-4 flex items-center justify-center gap-2">
                <label class="text-xs text-white/50">{{ $t('items.per_page_label') }}</label>
                <select v-model.number="perPage" class="rounded-lg border border-white/10 bg-black/20 px-2 py-1 text-xs text-white outline-none focus:border-sky-300">
                    <option v-for="size in perPageOptions" :key="size" :value="size" class="bg-black text-white">{{ size }}</option>
                </select>
            </div>

            <EmblemDetailDrawer :emblem="selectedEmblem" :open="isDrawerOpen" @close="closeEmblemDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmblemDetailDrawer from '@/Components/EmblemDetailDrawer.vue';

const { t } = useI18n();

const props = defineProps({
    emblems: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    tiers: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const tierFilter = ref(props.filters.tier ?? '');
const confidentialFilter = ref(props.filters.confidential ?? '');
const sortBy = ref(props.filters.sort ?? 'default');
const perPage = ref(props.filters.per_page ?? props.emblems?.per_page ?? 48);

const perPageOptions = [24, 48, 96, 192];

const selectedEmblem = ref(null);
const isDrawerOpen = ref(false);

const sortOptions = computed(() => [
    { value: 'default', label: t('items.sort_default') },
    { value: 'tier-desc', label: t('items.sort_tier_desc') },
    { value: 'tier-asc', label: t('items.sort_tier_asc') },
    { value: 'name-asc', label: t('items.sort_name_asc') },
    { value: 'name-desc', label: t('items.sort_name_desc') },
    { value: 'hash-asc', label: t('items.sort_hash_asc') },
    { value: 'hash-desc', label: t('items.sort_hash_desc') },
]);

const totalCount = computed(() => props.emblems?.meta?.total ?? props.emblems?.total ?? props.emblems?.data?.length ?? 0);

const availableTiers = computed(() =>
    props.tiers?.length
        ? props.tiers
        : Array.from(new Set((props.emblems?.data ?? []).map((emblem) => emblem.tier_type_name).filter(Boolean)))
);

/*
 * Couleur de bordure selon le tier_type numérique (identifiants Bungie D1 :
 * 2=Commun, 3=Peu commun, 4=Rare, 5=Légendaire, 6=Exotique) — indépendant
 * de la langue, contrairement au nom du tier.
 */
const tierBorderClass = (tierType) => {
    switch (Number(tierType)) {
        case 6: return 'border-yellow-500';
        case 5: return 'border-purple-500';
        case 4: return 'border-blue-500';
        case 3: return 'border-green-500';
        default: return 'border-white';
    }
};

/*
 * Couleur de fond des options du sélecteur de rareté : identique à la
 * couleur de bordure utilisée sur les cards, pour une lecture immédiate de
 * la rareté dans la liste déroulante. Police blanche dans tous les cas.
 */
const tierBgColor = (tierType) => {
    switch (Number(tierType)) {
        case 6: return '#eab308';
        case 5: return '#a855f7';
        case 4: return '#3b82f6';
        case 3: return '#22c55e';
        default: return '#000000';
    }
};

let searchTimer = null;
watch(search, (value) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => sendFilters(), 300);
});
watch([tierFilter, confidentialFilter, sortBy, perPage], () => sendFilters());

function sendFilters() {
    router.get(
        '/emblems',
        {
            search: search.value || undefined,
            tier: tierFilter.value || undefined,
            confidential: confidentialFilter.value || undefined,
            sort: sortBy.value || undefined,
            per_page: perPage.value === 48 ? undefined : perPage.value,
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

/*
 * Traduction des libellés de pagination générés par Laravel
 * ("&laquo; Previous" / "Next &raquo;") via les clés i18n.
 */
function linkLabel(label) {
    if (typeof label !== 'string') return label ?? '';
    if (label.includes('Previous')) return '&laquo; ' + t('items.prev_page');
    if (label.includes('Next')) return t('items.next_page') + ' &raquo;';
    return label;
}

function resetFilters() {
    search.value = '';
    tierFilter.value = '';
    confidentialFilter.value = '';
    sortBy.value = 'default';
    perPage.value = 48;
    if (searchTimer) { clearTimeout(searchTimer); searchTimer = null; }
    sendFilters();
}

function openEmblemDetail(emblem) { selectedEmblem.value = emblem; isDrawerOpen.value = true; }
function closeEmblemDetail() { isDrawerOpen.value = false; setTimeout(() => { selectedEmblem.value = null; }, 200); }

onMounted(() => {
    const f = usePage().props.filters ?? {};
    search.value = f.search ?? '';
    tierFilter.value = f.tier ?? '';
    confidentialFilter.value = f.confidential ?? '';
    sortBy.value = f.sort ?? 'default';
    perPage.value = f.per_page ?? 48;
});
</script>

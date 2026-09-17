<template>
    <AppLayout :title="$t('armors.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div><p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p><h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('armors.title') }}</h1></div>
                <span class="text-xs text-white/40">{{ $t('layout.records', { count: filteredArmors.length }) }}</span>
            </div>
            <div class="mb-6 flex flex-wrap gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="min-w-[200px] flex-1"><label class="mb-1 block text-xs text-white/50">{{ $t('items.search_label') }}</label><input v-model="search" type="text" :placeholder="$t('armors.search_placeholder')" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" /></div>
                <div class="min-w-[180px]"><label class="mb-1 block text-xs text-white/50">{{ $t('items.rarity_label') }}</label><select v-model="tierFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"><option value="">{{ $t('items.rarity_all') }}</option><option v-for="tier in availableTiers" :key="tier" :value="tier">{{ tier }}</option></select></div>
                <div class="min-w-[180px]"><label class="mb-1 block text-xs text-white/50">{{ $t('items.confidentiality_label') }}</label><select v-model="confidentialFilter" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"><option value="">{{ $t('items.confidentiality_all') }}</option><option value="classified">{{ $t('items.classified_only') }}</option><option value="public">{{ $t('items.public_only') }}</option></select></div>
                <div class="min-w-[180px]"><label class="mb-1 block text-xs text-white/50">{{ $t('items.sort_label') }}</label><select v-model="sortBy" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"><option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                <button @click="resetFilters" class="self-end px-2 py-2 text-sm text-white/50 underline transition hover:text-white">{{ $t('items.reset') }}</button>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                <div v-for="armor in filteredArmors" :key="armor.id" class="group relative cursor-pointer rounded-xl border-2 bg-white/[0.03] p-3 transition hover:-translate-y-1 hover:bg-white/[0.06]" :class="tierBorderClass(armor.tier_type_name)" @click="openArmorDetail(armor)">
                    <div class="absolute left-1 top-1 z-10 flex flex-col gap-1"><span v-if="['secret', 'censored', 'classified', 'beta', 'replaced'].includes(armor.subcategory_slug)" class="rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ armor.subcategory_slug }}</span></div>
                    <img v-if="armor.archive_icon_path" :src="armor.archive_icon_path" :alt="armor.name" class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100" />
                    <div v-else class="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-gray-900 text-xs text-gray-500">{{ $t('drawer.no_icon') }}</div>
                    <p class="truncate text-sm font-semibold text-white">{{ armor.name }}</p><p class="text-xs text-white/40">{{ armor.tier_type_name }}</p>
                </div>
            </div>
            <p v-if="filteredArmors.length === 0" class="py-12 text-center text-white/40">{{ $t('armors.empty_state') }}</p>
            <ArmorDetailDrawer :armor="selectedArmor" :open="isDrawerOpen" @close="closeArmorDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArmorDetailDrawer from '@/Components/ArmorDetailDrawer.vue';
const props = defineProps({ armors: { type: Array, required: true } });
const search = ref(''); const tierFilter = ref(''); const confidentialFilter = ref(''); const sortBy = ref('default');
const selectedArmor = ref(null); const isDrawerOpen = ref(false);
const availableTiers = computed(() => Array.from(new Set(props.armors.map((armor) => armor.tier_type_name).filter(Boolean))));
const sortOptions = [{ value: 'default', label: 'Par défaut' }, { value: 'tier-desc', label: 'Tier (décroissant)' }, { value: 'tier-asc', label: 'Tier (croissant)' }, { value: 'name-asc', label: 'Nom (A → Z)' }, { value: 'name-desc', label: 'Nom (Z → A)' }, { value: 'hash-asc', label: 'Hash (croissant)' }, { value: 'hash-desc', label: 'Hash (décroissant)' }];
const tierBorderClass = (tier) => { switch (String(tier).toLowerCase()) { case '6': case 'exotique': return 'border-yellow-500'; case '5': case 'légendaire': case 'legendary': return 'border-purple-500'; case '4': case 'rare': return 'border-blue-500'; case '3': case 'peu commun': case 'uncommon': return 'border-green-500'; default: return 'border-white'; } };
const filteredArmors = computed(() => { const confidential = ['classified', 'censored', 'secret']; const filtered = props.armors.filter((armor) => (search.value === '' || (armor.name ?? '').toLowerCase().includes(search.value.toLowerCase())) && (tierFilter.value === '' || armor.tier_type_name === tierFilter.value) && (confidentialFilter.value === '' || (confidentialFilter.value === 'classified' && confidential.includes(armor.subcategory_slug)) || (confidentialFilter.value === 'public' && !confidential.includes(armor.subcategory_slug)))); if (sortBy.value === 'default') return filtered; const sorted = [...filtered]; const compareName = (a, b) => (a.name ?? '').localeCompare(b.name ?? '', 'fr', { sensitivity: 'base', numeric: true }); switch (sortBy.value) { case 'tier-desc': sorted.sort((a, b) => b.tier_type - a.tier_type || compareName(a, b)); break; case 'tier-asc': sorted.sort((a, b) => a.tier_type - b.tier_type || compareName(a, b)); break; case 'name-asc': sorted.sort(compareName); break; case 'name-desc': sorted.sort((a, b) => compareName(b, a)); break; case 'hash-asc': sorted.sort((a, b) => (a.hash ?? 0) - (b.hash ?? 0)); break; case 'hash-desc': sorted.sort((a, b) => (b.hash ?? 0) - (a.hash ?? 0)); break; } return sorted; });
function resetFilters() { search.value = ''; tierFilter.value = ''; confidentialFilter.value = ''; sortBy.value = 'default'; }
function openArmorDetail(armor) { selectedArmor.value = armor; isDrawerOpen.value = true; }
function closeArmorDetail() { isDrawerOpen.value = false; setTimeout(() => { selectedArmor.value = null; }, 200); }
</script>

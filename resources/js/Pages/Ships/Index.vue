<template>
    <AppLayout title="Vaisseaux">
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-6 text-white">Vaisseaux</h1>

            <!-- Filtres -->
            <div class="flex flex-wrap gap-4 mb-6 bg-gray-800 p-4 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">Recherche</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Nom du vaisseau..."
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    />
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">Rareté</label>
                    <select
                        v-model="tierFilter"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    >
                        <option value="">Toutes</option>
                        <option v-for="tier in availableTiers" :key="tier" :value="tier">
                            {{ tier }}
                        </option>
                    </select>
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">Confidentialité</label>
                    <select
                        v-model="confidentialFilter"
                        class="w-full bg-gray-900 text-white text-sm rounded px-3 py-2 border border-gray-700 focus:border-blue-400 focus:outline-none"
                    >
                        <option value="">Tous</option>
                        <option value="confidential">Confidentiels uniquement</option>
                        <option value="public">Publics uniquement</option>
                    </select>
                </div>

                <div class="min-w-[180px]">
                    <label class="block text-xs text-gray-400 mb-1">{{ $t('ships.sort_label') }}</label>
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
                        Réinitialiser
                    </button>
                </div>
            </div>

            <p class="text-sm text-gray-400 mb-4">
                {{ filteredShips.length }} vaisseau{{ filteredShips.length > 1 ? 'x' : '' }} trouvé{{ filteredShips.length > 1 ? 's' : '' }}
            </p>

            <!-- Grille unique -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-12 gap-4">
                <div
                    v-for="ship in filteredShips"
                    :key="ship.id"
                    class="relative bg-gray-800 rounded-lg p-3 hover:bg-gray-700 transition cursor-pointer border"
                    :class="tierBorderClass(ship.tier_type_name)"
                    @click="openShipDetail(ship)"
                >
                    <!-- Badges -->
                    <div class="absolute top-1 left-1 flex flex-col gap-1 z-10">
                        <span
                            v-if="ship.subcategory_slug === 'confidential'"
                            class="text-[10px] bg-red-700 text-white px-1.5 py-0.5 rounded font-semibold"
                            title="Objet rendu confidentiel par Bungie, connu de la communauté"
                        >
                            Confidentiel
                        </span>
                    </div>

                    <img
                        v-if="ship.archive_icon_path"
                        :src="ship.archive_icon_path"
                        :alt="ship.name"
                        class="w-full aspect-square object-cover rounded mb-2"
                    />
                    <div
                        v-else
                        class="w-full aspect-square bg-gray-900 rounded mb-2 flex items-center justify-center text-gray-500 text-xs"
                    >
                        Pas d'icône
                    </div>

                    <p class="text-sm text-white font-semibold truncate">{{ ship.name }}</p>
                    <p class="text-xs text-gray-400">{{ ship.tier_type_name }}</p>
                </div>
            </div>

            <p v-if="filteredShips.length === 0" class="text-center text-gray-500 py-12">
                Aucun vaisseau ne correspond à ces critères.
            </p>

            <!-- Drawer de détails -->
            <ShipDetailDrawer
                :ship="selectedShip"
                :open="isDrawerOpen"
                @close="closeShipDetail"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShipDetailDrawer from '@/Components/ShipDetailDrawer.vue';

const props = defineProps({
    ships: {
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
    const tiers = new Set(props.ships.map((s) => s.tier_type_name).filter(Boolean));
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

const filteredShips = computed(() => {
    const filtered = props.ships.filter((ship) => {
        const matchesSearch =
            search.value === '' ||
            ship.name.toLowerCase().includes(search.value.toLowerCase());

        const matchesTier =
            tierFilter.value === '' || ship.tier_type_name === tierFilter.value;

        const matchesConfidential =
            confidentialFilter.value === '' ||
            (confidentialFilter.value === 'classified' &&
                ['classified', 'censored', 'secret'].includes(ship.subcategory_slug)) ||
            (confidentialFilter.value === 'public' &&
                !['classified', 'censored', 'secret'].includes(ship.subcategory_slug));

        const matchesCdn =
            cdnFilter.value === '' ||
            (cdnFilter.value === 'available' && ship.icon_downloaded) ||
            (cdnFilter.value === 'unavailable' && !ship.icon_downloaded);

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

function tierBorderClass(tierName) {
    switch (tierName) {
        case 'Exotique':
            return 'border-yellow-500';
        case 'Légendaire':
            return 'border-purple-500';
        case 'Rare':
            return 'border-blue-500';
        case 'Peu commun':
            return 'border-green-500';
        default:
            return 'border-gray-600';
    }
}

const selectedShip = ref(null);
const isDrawerOpen = ref(false);

function openShipDetail(ship) {
    selectedShip.value = ship;
    isDrawerOpen.value = true;
}

function closeShipDetail() {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedShip.value = null;
    }, 200);
}
</script>
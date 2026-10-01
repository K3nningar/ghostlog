<template>
    <AppLayout :title="$t('collections.ships_title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 border-b border-white/10 pb-5">
                <p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p>
                <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('collections.ships_title') }}</h1>
                <p class="mt-2 text-xs text-white/40">{{ $t('collections.vendor_hash') }}: {{ vendorHash }}</p>
            </div>

            <div v-if="categories.length" class="space-y-10">
                <section v-for="category in categories" :key="category.hash">
                    <div class="mb-4 flex items-baseline justify-between gap-3 border-b border-white/10 pb-3">
                        <h2 class="text-xl font-medium text-white">{{ category.name }}</h2>
                        <span class="text-xs text-white/40">{{ category.ships.length }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                        <button
                            v-for="ship in category.ships"
                            :key="ship.id"
                            type="button"
                            class="group rounded-xl border-2 bg-white/[0.03] p-3 text-left transition hover:-translate-y-1 hover:bg-white/[0.06]"
                            :class="tierBorderClass(ship.tier_type)"
                            @click="openShipDetail(ship)"
                        >
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
                        </button>
                    </div>
                </section>
            </div>

            <p v-else class="py-12 text-center text-white/40">{{ $t('collections.ships_empty_state') }}</p>

            <ShipDetailDrawer :ship="selectedShip" :open="isDrawerOpen" @close="closeShipDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ShipDetailDrawer from '@/Components/ShipDetailDrawer.vue';

defineProps({
    vendorHash: { type: String, required: true },
    categories: { type: Array, required: true },
});

const selectedShip = ref(null);
const isDrawerOpen = ref(false);

const tierBorderClass = (tierType) => {
    switch (Number(tierType)) {
        case 6: return 'border-yellow-500';
        case 5: return 'border-purple-500';
        case 4: return 'border-blue-500';
        case 3: return 'border-green-500';
        default: return 'border-white';
    }
};

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

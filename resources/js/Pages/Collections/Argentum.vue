<template>
    <AppLayout :title="$t('collections.argentum_title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 border-b border-white/10 pb-5">
                <p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p>
                <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('collections.argentum_title') }}</h1>
                <p class="mt-2 text-xs text-white/40">{{ $t('collections.vendor_hash') }}: {{ vendorHash }}</p>
            </div>

            <div v-if="categories.length" class="space-y-10">
                <section v-for="category in categories" :key="`${category.type}-${category.hash}`">
                    <div class="mb-4 flex items-baseline justify-between gap-3 border-b border-white/10 pb-3">
                        <h2 class="text-xl font-medium text-white">{{ category.name }}</h2>
                        <span class="text-xs text-white/40">{{ category.items.length }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                        <button
                            v-for="item in category.items"
                            :key="item.id"
                            type="button"
                            class="group rounded-xl border-2 bg-white/[0.03] p-3 text-left transition hover:-translate-y-1 hover:bg-white/[0.06]"
                            :class="tierBorderClass(item.tier_type)"
                            @click="openItemDetail(item)"
                        >
                            <img
                                v-if="item.archive_icon_path"
                                :src="item.archive_icon_path"
                                :alt="item.name"
                                loading="lazy"
                                class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100"
                            />
                            <div v-else class="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-gray-900 text-xs text-gray-500">{{ $t('drawer.no_icon') }}</div>
                            <p class="truncate text-sm font-semibold text-white">{{ item.name }}</p>
                            <p class="text-xs text-white/40">{{ item.tier_type_name }}</p>
                        </button>
                    </div>
                </section>
            </div>

            <p v-else class="py-12 text-center text-white/40">{{ $t('collections.argentum_empty_state') }}</p>

            <EmblemDetailDrawer :emblem="selectedItem?.category_slug === 'emblems' ? selectedItem : null" :open="isDrawerOpen && selectedItem?.category_slug === 'emblems'" @close="closeItemDetail" />
            <ShipDetailDrawer :ship="selectedItem?.category_slug === 'ships' ? selectedItem : null" :open="isDrawerOpen && selectedItem?.category_slug === 'ships'" @close="closeItemDetail" />
            <SparrowDetailDrawer :sparrow="selectedItem?.category_slug === 'sparrows' ? selectedItem : null" :open="isDrawerOpen && selectedItem?.category_slug === 'sparrows'" @close="closeItemDetail" />
            <ArmorDetailDrawer :armor="selectedItem?.category_slug === 'armors' ? selectedItem : null" :open="isDrawerOpen && selectedItem?.category_slug === 'armors'" @close="closeItemDetail" />
            <ItemDetailDrawer :item="selectedItem && !['emblems', 'ships', 'sparrows', 'armors'].includes(selectedItem.category_slug) ? selectedItem : null" :open="isDrawerOpen && selectedItem && !['emblems', 'ships', 'sparrows', 'armors'].includes(selectedItem.category_slug)" @close="closeItemDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmblemDetailDrawer from '@/Components/EmblemDetailDrawer.vue';
import ShipDetailDrawer from '@/Components/ShipDetailDrawer.vue';
import SparrowDetailDrawer from '@/Components/SparrowDetailDrawer.vue';
import ArmorDetailDrawer from '@/Components/ArmorDetailDrawer.vue';
import ItemDetailDrawer from '@/Components/ItemDetailDrawer.vue';

defineProps({
    vendorHash: { type: String, required: true },
    categories: { type: Array, required: true },
});

const selectedItem = ref(null);
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

function openItemDetail(item) {
    selectedItem.value = item;
    isDrawerOpen.value = true;
}

function closeItemDetail() {
    isDrawerOpen.value = false;
    setTimeout(() => {
        selectedItem.value = null;
    }, 200);
}
</script>

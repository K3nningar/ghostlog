<template>
    <AppLayout :title="$t('collections.sparrows_title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 border-b border-white/10 pb-5">
                <p class="mb-2 text-xs uppercase tracking-[0.24em] text-sky-300">{{ $t('layout.archive_index') }}</p>
                <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('collections.sparrows_title') }}</h1>
                <p class="mt-2 text-xs text-white/40">{{ $t('collections.vendor_hash') }}: {{ vendorHash }}</p>
            </div>

            <div v-if="categories.length" class="space-y-10">
                <section v-for="category in categories" :key="category.hash">
                    <div class="mb-4 flex items-baseline justify-between gap-3 border-b border-white/10 pb-3">
                        <h2 class="text-xl font-medium text-white">{{ category.name }}</h2>
                        <span class="text-xs text-white/40">{{ category.sparrows.length }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">
                        <button
                            v-for="sparrow in category.sparrows"
                            :key="sparrow.id"
                            type="button"
                            class="group rounded-xl border-2 bg-white/[0.03] p-3 text-left transition hover:-translate-y-1 hover:bg-white/[0.06]"
                            :class="tierBorderClass(sparrow.tier_type)"
                            @click="openSparrowDetail(sparrow)"
                        >
                            <img
                                v-if="sparrow.archive_icon_path"
                                :src="sparrow.archive_icon_path"
                                :alt="sparrow.name"
                                loading="lazy"
                                class="mb-2 aspect-square w-full rounded-lg object-cover opacity-90 transition group-hover:opacity-100"
                            />
                            <div v-else class="mb-2 flex aspect-square w-full items-center justify-center rounded-lg bg-gray-900 text-xs text-gray-500">{{ $t('drawer.no_icon') }}</div>
                            <p class="truncate text-sm font-semibold text-white">{{ sparrow.name }}</p>
                            <p class="text-xs text-white/40">{{ sparrow.tier_type_name }}</p>
                        </button>
                    </div>
                </section>
            </div>

            <p v-else class="py-12 text-center text-white/40">{{ $t('collections.sparrows_empty_state') }}</p>

            <SparrowDetailDrawer :sparrow="selectedSparrow" :open="isDrawerOpen" @close="closeSparrowDetail" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SparrowDetailDrawer from '@/Components/SparrowDetailDrawer.vue';

defineProps({
    vendorHash: { type: String, required: true },
    categories: { type: Array, required: true },
});

const selectedSparrow = ref(null);
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

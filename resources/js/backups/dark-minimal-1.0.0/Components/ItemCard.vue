<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const tierColors = {
    Exotic: 'border-yellow-500 bg-yellow-950/20',
    Legendary: 'border-purple-500 bg-purple-950/20',
    Rare: 'border-blue-500 bg-blue-950/20',
    Uncommon: 'border-green-500 bg-green-950/20',
    Common: 'border-gray-500 bg-gray-950/20',
    Basic: 'border-gray-600 bg-gray-950/10',
};

const tierClass = (tierName) => {
    return tierColors[tierName] ?? 'border-gray-700 bg-gray-950/10';
};

const onImgError = (event) => {
    event.target.src = '/images/item-placeholder.png';
};
</script>

<template>
    <Link
        :href="`/items/${item.hash}`"
        class="group relative flex flex-col rounded-lg border-2 p-3 transition hover:scale-[1.02] hover:shadow-lg"
        :class="tierClass(item.tier_type_name)"
    >
        <div class="relative mb-2 aspect-square overflow-hidden rounded-md bg-black/40">
            <img
                :src="item.display_icon"
                :alt="item.name"
                loading="lazy"
                @error="onImgError"
                class="h-full w-full object-cover transition group-hover:scale-105"
            />

            <span
                v-if="item.tier_type_name"
                class="absolute bottom-1 right-1 rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white"
            >
                {{ item.tier_type_name }}
            </span>
        </div>

        <h3 class="truncate text-sm font-semibold text-white" :title="item.name">
            {{ item.name }}
        </h3>

        <p v-if="item.item_type_name" class="truncate text-xs text-gray-400">
            {{ item.item_type_name }}
        </p>
    </Link>
</template>
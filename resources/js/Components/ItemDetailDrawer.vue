<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-40 bg-black/60" @click="$emit('close')"></div>
        </Transition>
        <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="open && item" class="fixed right-0 top-0 z-50 h-full w-full max-w-md overflow-y-auto bg-neutral-900 shadow-2xl">
                <button type="button" class="absolute right-4 top-4 z-10 rounded-full bg-black/50 px-3 py-2 text-white hover:bg-black/70" @click="$emit('close')" :aria-label="$t('drawer.close')">×</button>
                <div class="flex min-h-64 items-center justify-center bg-neutral-800 p-8">
                    <img v-if="item.archive_icon_path" :src="item.archive_icon_path" :alt="item.name" class="max-h-48 max-w-full object-contain" />
                    <span v-else class="text-sm text-neutral-500">{{ $t('drawer.no_icon') }}</span>
                </div>
                <div class="space-y-5 p-6">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ item.name }}</h2>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">{{ item.tier_type_name }}</p>
                    </div>
                    <p v-if="item.description" class="text-sm leading-relaxed text-gray-300">{{ item.description }}</p>
                    <div v-if="item.hash" class="border-t border-gray-800 pt-4">
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-400">{{ $t('emblems.hash_label') }}</h3>
                        <p class="text-sm text-neutral-300">{{ item.hash }}</p>
                    </div>
                    <img v-if="item.ingame_image_path" :src="item.ingame_image_path" :alt="item.name" class="w-full rounded-lg object-contain" />
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
defineProps({
    item: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

defineEmits(['close']);
</script>

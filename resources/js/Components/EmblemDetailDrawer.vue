<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-40 bg-black/60" @click="close"></div>
        </Transition>
        <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="open && emblem" class="fixed right-0 top-0 z-50 h-full w-full max-w-md overflow-y-auto bg-neutral-900 shadow-2xl">
                <button class="absolute right-4 top-4 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70" @click="close" :aria-label="$t('drawer.close')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div class="relative flex w-full items-center justify-center p-6">
                    <div class="flex h-40 max-w-full items-center justify-center rounded bg-neutral-900 cursor-pointer" @click="openMediaModal">
                        <img
                            v-if="emblem.archive_icon_path"
                            :src="emblem.archive_icon_path"
                            :alt="emblem.name"
                            style="height:70px;"
                        />
                        <img
                            v-if="emblem.archive_icon_path_secondary"
                            :src="emblem.archive_icon_path_secondary"
                            :alt="emblem.name"
                            style="height: 70px; margin-left: -4px;"
                            @click="openMediaModal"
                        />
                        <span v-else class="text-center text-xs text-neutral-500">{{ $t('drawer.no_icon') }}</span>
                    </div>
                    <span v-if="emblem.subcategory_slug === 'secret'" class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-purple-600/90 text-white">{{ $t('items.secret_item') }}</span>
                    <span v-if="emblem.subcategory_slug === 'censored'" class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-red-600/90 text-white">{{ $t('items.censored_item') }}</span>
                    <span v-else-if="emblem.subcategory_slug === 'classified'" class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-orange-700/90 text-white">{{ $t('items.classified_item') }}</span>
                    <span v-else-if="emblem.subcategory_slug === 'beta'" class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-blue-500/90 text-white">{{ $t('items.beta_item') }}</span>
                    <span v-else-if="emblem.subcategory_slug === 'replaced'" class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-gray-500/90 text-white">{{ $t('items.replaced_item') }}</span>
                </div>

                <div class="space-y-6 p-6">
                    <div>
                        <h2 class="truncate text-xl font-bold text-white">{{ emblem.name }}</h2>
                    </div>
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">{{ emblem.tier_type_name }}</p>
                    <div v-if="emblem.hash">
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-400">{{ $t('emblems.hash_label') }}</h3>
                        <p class="text-sm leading-relaxed text-neutral-300">{{ emblem.hash }}</p>
                    </div>
                    <span v-if="emblem.subcategory_slug === 'secret'" class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block">{{ $t('items.secret_item_description') }}</span>
                    <span v-if="emblem.subcategory_slug === 'censored'" class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block">{{ $t('items.censored_item_description') }}</span>
                    <span v-if="emblem.subcategory_slug === 'classified'" class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block">{{ $t('items.classified_item_description') }}</span>
                    <span v-if="emblem.subcategory_slug === 'beta'" class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block">{{ $t('items.beta_item_description') }}</span>
                    <span v-if="emblem.subcategory_slug === 'replaced'" class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block">{{ $t('items.replaced_item_description') }}</span>

                    <div v-if="otherLocales.length" class="mt-6 border-t border-gray-800 pt-4">
                        <h3 class="text-sm font-semibold text-gray-300 mb-2">{{ $t('drawer.other_languages') }}</h3>
                        <ul class="space-y-1">
                            <li v-for="translation in otherLocales" :key="translation.locale" class="text-sm bg-gray-800 rounded px-3 py-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <Icon :icon="localeFlags[translation.locale] || 'flagpack:xx'" class="w-4 h-4" :title="localeLabels[translation.locale] || translation.locale" />
                                    <p class="text-white font-medium">{{ translation.name }}</p>
                                </div>
                                <p v-if="translation.description" class="text-gray-400 text-xs mt-1 leading-relaxed">{{ translation.description }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </Transition>

        <Transition name="fade">
            <div
                v-if="isMediaModalOpen"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
                @click="closeMediaModal"
            >
                <button
                    class="absolute right-4 top-4 text-2xl leading-none text-gray-300 hover:text-white"
                    @click="closeMediaModal"
                    :aria-label="$t('drawer.close')"
                >
                    ✕
                </button>
                <div
                    class="flex h-[min(40vh,20rem)] max-h-[90vh] max-w-[90vw] items-center rounded bg-neutral-900 p-6 shadow-2xl"
                    @click.stop
                >
                    <div class="flex h-full items-center justify-center bg-black/20">
                        <img
                            v-if="emblem?.archive_icon_path"
                            :src="emblem.archive_icon_path"
                            :alt="emblem.name"
                            class="h-full w-auto object-contain"
                        />
                        <img
                            v-if="emblem?.archive_icon_path_secondary"
                            :src="emblem.archive_icon_path_secondary"
                            :alt="emblem.name"
                            class="h-full w-auto max-w-full object-contain"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { localeFlags, localeLabels } from '@/constants/localeFlags';

const props = defineProps({
    emblem: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);
const translations = ref([]);
const isMediaModalOpen = ref(false);

function close() { emit('close'); }
function openMediaModal() { isMediaModalOpen.value = true; }
function closeMediaModal() { isMediaModalOpen.value = false; }

async function fetchTranslations(hash) {
    if (!hash) { translations.value = []; return; }
    try {
        const { data } = await axios.get(`/items/${hash}/locales`);
        translations.value = data;
    } catch (error) {
        translations.value = [];
    }
}

const otherLocales = computed(() => translations.value.filter((translation) => translation.locale !== props.emblem?.locale));

watch(() => props.emblem, (newEmblem) => {
    if (newEmblem?.hash) fetchTranslations(newEmblem.hash);
    else translations.value = [];
    isMediaModalOpen.value = false;
}, { immediate: true });

watch(() => props.open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
    if (!isOpen) isMediaModalOpen.value = false;
});

function handleKeydown(event) {
    if (event.key !== 'Escape') return;
    if (isMediaModalOpen.value) closeMediaModal();
    else if (props.open) close();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-40 bg-black/60" @click="close"></div>
        </Transition>
        <Transition enter-active-class="transition-transform duration-300 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="open && weapon" class="fixed right-0 top-0 z-50 h-full w-full max-w-md overflow-y-auto bg-neutral-900 shadow-2xl">
                <button class="absolute right-4 top-4 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70" @click="close" :aria-label="$t('drawer.close')">×</button>
                <div class="relative h-64 w-full bg-neutral-800">
                    <img v-if="weapon.ingame_image_path && !isVideoPath(weapon.ingame_image_path)" :src="weapon.ingame_image_path" :alt="weapon.name" class="h-full w-full cursor-pointer object-cover" @click="openMediaModal" />
                    <video v-else-if="weapon.ingame_image_path" :src="weapon.ingame_image_path" :aria-label="weapon.name" class="h-full w-full cursor-pointer object-cover" autoplay loop muted playsinline @click.self="openMediaModal"></video>
                    <div v-else class="flex h-full w-full items-center justify-center text-neutral-500">{{ $t('drawer.no_icon') }}</div>
                    <span v-if="weapon.subcategory_slug === 'secret'" class="absolute left-4 top-4 rounded bg-purple-600/90 px-2 py-1 text-xs font-semibold uppercase text-white">{{ $t('items.secret_item') }}</span>
                    <span v-if="weapon.subcategory_slug === 'censored'" class="absolute left-4 top-4 rounded bg-red-600/90 px-2 py-1 text-xs font-semibold uppercase text-white">{{ $t('items.censored_item') }}</span>
                    <span v-else-if="weapon.subcategory_slug === 'classified'" class="absolute left-4 top-4 rounded bg-orange-700/90 px-2 py-1 text-xs font-semibold uppercase text-white">{{ $t('items.classified_item') }}</span>
                    <span v-else-if="weapon.subcategory_slug === 'beta'" class="absolute left-4 top-4 rounded bg-blue-500/90 px-2 py-1 text-xs font-semibold uppercase text-white">{{ $t('items.beta_item') }}</span>
                    <span v-else-if="weapon.subcategory_slug === 'replaced'" class="absolute left-4 top-4 rounded bg-gray-500/90 px-2 py-1 text-xs font-semibold uppercase text-white">{{ $t('items.replaced_item') }}</span>
                </div>
                <div class="space-y-6 p-6">
                    <div class="flex items-center gap-5">
                        <img v-if="weapon.archive_icon_path" :src="weapon.archive_icon_path" :alt="weapon.name" class="h-12 w-12 flex-shrink-0 rounded object-cover" />
                        <h2 class="truncate text-xl font-bold text-white">{{ weapon.name }}</h2>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-400">{{ weapon.tier_type_name }}</p>
                    <p v-if="weapon.description" class="text-sm italic leading-relaxed text-gray-300">{{ weapon.description }}</p>
                    <div v-if="weapon.hash"><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-400">{{ $t('weapons.hash_label') }}</h3><p class="text-sm text-neutral-300">{{ weapon.hash }}</p></div>
                    <span v-if="weapon.subcategory_slug === 'secret'" class="inline-block rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.secret_item_description') }}</span>
                    <span v-if="weapon.subcategory_slug === 'censored'" class="inline-block rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.censored_item_description') }}</span>
                    <span v-if="weapon.subcategory_slug === 'classified'" class="inline-block rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.classified_item_description') }}</span>
                    <span v-if="weapon.subcategory_slug === 'beta'" class="inline-block rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.beta_item_description') }}</span>
                    <span v-if="weapon.subcategory_slug === 'replaced'" class="inline-block rounded bg-gray-700 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $t('items.replaced_item_description') }}</span>
                    <div v-if="otherLocales.length" class="border-t border-gray-800 pt-4">
                        <h3 class="mb-2 text-sm font-semibold text-gray-300">{{ $t('drawer.other_languages') }}</h3>
                        <ul class="space-y-1"><li v-for="translation in otherLocales" :key="translation.locale" class="rounded bg-gray-800 px-3 py-2 text-sm"><div class="flex items-center gap-2"><Icon :icon="localeFlags[translation.locale] || 'flagpack:xx'" class="h-4 w-4" /><p class="font-medium text-white">{{ translation.name }}</p></div><p v-if="translation.description" class="mt-1 text-xs leading-relaxed text-gray-400">{{ translation.description }}</p></li></ul>
                    </div>
                </div>
            </aside>
        </Transition>
        <Transition name="fade">
            <div v-if="isMediaModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4" @click="closeMediaModal">
                <button class="absolute right-4 top-4 text-2xl text-gray-300 hover:text-white" @click="closeMediaModal" :aria-label="$t('drawer.close')">×</button>
                <img v-if="weapon?.ingame_image_path && !isVideoPath(weapon.ingame_image_path)" :src="weapon.ingame_image_path" :alt="weapon.name" class="max-h-[90vh] max-w-[90vw] object-contain" @click.stop />
                <video v-else-if="weapon?.ingame_image_path" :src="weapon.ingame_image_path" :aria-label="weapon.name" class="max-h-[90vh] max-w-[90vw]" autoplay loop muted playsinline @click.stop></video>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { localeFlags } from '@/constants/localeFlags';

const props = defineProps({ weapon: { type: Object, default: null }, open: { type: Boolean, default: false } });
const emit = defineEmits(['close']);
const translations = ref([]);
const isMediaModalOpen = ref(false);
function close() { emit('close'); }
function openMediaModal() { isMediaModalOpen.value = true; }
function closeMediaModal() { isMediaModalOpen.value = false; }
function isVideoPath(path) { return /\.(mp4|webm|ogg|ogv|mov|m4v)(?:$|[?#])/i.test(path); }
async function fetchTranslations(hash) {
    if (!hash) { translations.value = []; return; }
    try { translations.value = (await axios.get(`/items/${hash}/locales`)).data; } catch { translations.value = []; }
}
const otherLocales = computed(() => translations.value.filter((translation) => translation.locale !== props.weapon?.locale));
watch(() => props.weapon, (weapon) => { if (weapon?.hash) fetchTranslations(weapon.hash); else translations.value = []; isMediaModalOpen.value = false; }, { immediate: true });
watch(() => props.open, (open) => { document.body.style.overflow = open ? 'hidden' : ''; if (!open) isMediaModalOpen.value = false; });
function handleKeydown(event) { if (event.key !== 'Escape') return; if (isMediaModalOpen.value) closeMediaModal(); else if (props.open) close(); }
onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => { window.removeEventListener('keydown', handleKeydown); document.body.style.overflow = ''; });
</script>

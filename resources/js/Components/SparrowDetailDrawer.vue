<template>
    <Teleport to="body">
        <!-- Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-40 bg-black/60"
                @click="close"
            ></div>
        </Transition>

        <!-- Drawer -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <aside
                v-if="open && sparrow"
                class="fixed right-0 top-0 z-50 h-full w-full max-w-md overflow-y-auto bg-neutral-900 shadow-2xl"
            >
                <!-- Bouton fermer -->
                <button
                    class="absolute right-4 top-4 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70"
                    @click="close"
                    aria-label="Fermer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Média en haut -->
                <div class="relative h-64 w-full bg-neutral-800">
                    <img
                        v-if="sparrow.ingame_image_path && !isVideoPath(sparrow.ingame_image_path)"
                        :src="sparrow.ingame_image_path"
                        :alt="sparrow.name"
                        class="h-full w-full object-cover cursor-pointer"
                        @click="openImageModal"
                    />
                    <video
                        v-else-if="sparrow.ingame_image_path"
                        :src="sparrow.ingame_image_path"
                        :aria-label="sparrow.name"
                        class="h-full w-full object-cover cursor-pointer"
                        autoplay
                        loop
                        muted
                        playsinline
                        @click.self="openImageModal"
                    ></video>
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-neutral-500"
                    >
                        {{ $t('drawer.no_icon') }}
                    </div>

                    <!-- Confidentiality type badge -->
                    <span
                        v-if="sparrow.subcategory_slug === 'secret'"
                        class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-purple-600/90 text-white"
                    >
                        {{ $t('items.secret_item') }}
                    </span>
                    <span
                        v-if="sparrow.subcategory_slug === 'censored'"
                        class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-red-600/90 text-white"
                    >
                        {{ $t('items.censored_item') }}
                    </span>
                    <span
                        v-else-if="sparrow.subcategory_slug === 'classified'"
                        class="absolute left-4 top-4 rounded px-2 py-1 text-xs font-semibold uppercase bg-orange-700/90 text-white"
                    >
                        {{ $t('items.classified_item') }}
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Icône + Nom -->
                    <div class="flex items-center gap-5">
                        <img
                            v-if="sparrow.archive_icon_path"
                            :src="sparrow.archive_icon_path"
                            :alt="sparrow.name"
                            class="h-12 w-12 rounded object-cover flex-shrink-0"
                        />
                        <h2 class="text-xl font-bold text-white truncate">{{ sparrow.name }}</h2>
                    </div>

                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">{{ sparrow.tier_type_name }}</p>

                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-400">Description</h3>
                    <span v-if="sparrow.description" class="text-sm text-gray-300 italic leading-relaxed">
                        {{ sparrow.description }}
                    </span>

                    <div v-if="sparrow.hash">
                        <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-neutral-400">Hash</h3>
                        <p class="text-sm leading-relaxed text-neutral-300">{{ sparrow.hash }}</p>
                    </div>

                    <span
                        v-if="sparrow.subcategory_slug === 'secret'"
                        class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block"
                    >
                        {{ $t('items.secret_item_description') }}
                    </span>
                    <span
                        v-if="sparrow.subcategory_slug === 'censored'"
                        class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block"
                    >
                        {{ $t('items.censored_item_description') }}
                    </span>
                    <span
                        v-if="sparrow.subcategory_slug === 'classified'"
                        class="text-[10px] bg-gray-700 text-white px-1.5 py-0.5 rounded font-semibold inline-block"
                    >
                        {{ $t('items.classified_item_description') }}
                    </span>

                    <!-- Traductions -->
                    <div v-if="otherLocales.length" class="mt-6 border-t border-gray-800 pt-4">
                        <h3 class="text-sm font-semibold text-gray-300 mb-2">
                            {{ $t('drawer.other_languages') }}
                        </h3>
                        <ul class="space-y-1">
                            <li
                                v-for="translation in otherLocales"
                                :key="translation.locale"
                                class="text-sm bg-gray-800 rounded px-3 py-2"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <Icon
                                        :icon="localeFlags[translation.locale] || 'flagpack:xx'"
                                        class="w-4 h-4"
                                        :title="localeLabels[translation.locale] || translation.locale"
                                    />
                                    <p class="text-white font-medium">{{ translation.name }}</p>
                                </div>
                                <p v-if="translation.description" class="text-gray-400 text-xs mt-1 leading-relaxed">
                                    {{ translation.description }}
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>

    <!-- Modale média en jeu -->
    <Transition name="fade">
        <div
            v-if="isImageModalOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
            @click="closeImageModal"
        >
            <button
                @click="closeImageModal"
                class="absolute top-4 right-4 text-gray-300 hover:text-white text-2xl leading-none"
                aria-label="Fermer"
            >
                ✕
            </button>
            <img
                v-if="sparrow?.ingame_image_path && !isVideoPath(sparrow.ingame_image_path)"
                :src="sparrow?.ingame_image_path"
                :alt="sparrow?.name"
                class="max-h-[90vh] max-w-[90vw] object-contain rounded shadow-2xl"
                @click.stop
            />
            <video loop
                v-else-if="sparrow?.ingame_image_path"
                :src="sparrow.ingame_image_path"
                :aria-label="sparrow.name"
                class="max-h-[90vh] max-w-[90vw] rounded shadow-2xl"
                autoplay
                muted
                playsinline
                @click.stop
            ></video>
        </div>
    </Transition>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Icon } from '@iconify/vue';
import { localeFlags, localeLabels } from '@/constants/localeFlags';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    sparrow: {
        type: Object,
        default: null,
    },
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const translations = ref([]);
const isImageModalOpen = ref(false);

function close() {
    emit('close');
}

function openImageModal() {
    isImageModalOpen.value = true;
}

function closeImageModal() {
    isImageModalOpen.value = false;
}

function isVideoPath(path) {
    return /\.(mp4|webm|ogg|ogv|mov|m4v)(?:$|[?#])/i.test(path);
}

async function fetchTranslations(hash) {
    if (!hash) {
        translations.value = [];
        return;
    }

    try {
        const { data } = await axios.get(`/items/${hash}/locales`);
        translations.value = data;
    } catch (e) {
        translations.value = [];
    }
}

const otherLocales = computed(() =>
    translations.value.filter((t) => t.locale !== props.sparrow?.locale)
);

watch(
    () => props.sparrow,
    (newSparrow) => {
        if (newSparrow?.hash) {
            fetchTranslations(newSparrow.hash);
        } else {
            translations.value = [];
        }
        // Ferme la modale image si le sparrow change
        isImageModalOpen.value = false;
    },
    { immediate: true }
);

watch(
    () => props.open,
    (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
        if (!isOpen) {
            isImageModalOpen.value = false;
        }
    }
);

function handleKeydown(e) {
    if (e.key === 'Escape') {
        if (isImageModalOpen.value) {
            closeImageModal();
        } else if (props.open) {
            close();
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>
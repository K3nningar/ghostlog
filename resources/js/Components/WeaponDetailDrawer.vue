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

                    <!--
                        Section Perks :
                          - On n'utilise plus weapon.perks depuis les props (les
                            perks ne sont plus chargés sur la grille pour gagner
                            ~80k lignes pivot).
                          - On lit `perks.value` (état local) récupéré via axios
                            dans `fetchPerks()`.
                          - `perksByColumn` groupe par pivot.column et trie
                            chaque colonne par pivot.node_index.
                          - Tri par hash (pas id) — les échanges précédents ont
                            montré que les hashs sont la clé métier stable entre
                            langues ; l'auto-incrément id, lui, change entre
                            traductions.
                    -->
                    <div v-if="perksLoading" class="border-t border-gray-800 pt-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-400">{{ $t('weapons.perks_label') }}</h3>
                        <ul class="space-y-2">
                            <li v-for="n in 3" :key="n" class="flex animate-pulse items-center gap-3 rounded-lg bg-gray-800/40 px-3 py-2">
                                <div class="h-10 w-10 flex-shrink-0 rounded bg-gray-700"></div>
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="h-3 w-2/3 rounded bg-gray-700"></div>
                                    <div class="h-2 w-1/2 rounded bg-gray-800"></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div v-else-if="perksByColumn.length" class="border-t border-gray-800 pt-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-neutral-400">{{ $t('weapons.perks_label') }}</h3>

                        <div class="space-y-4">
                            <div v-for="group in perksByColumn" :key="group.column">
                                <div class="flex gap-2 overflow-x-auto pb-1">
                                    <div
                                        v-for="perk in group.perks"
                                        :key="perk.hash"
                                        class="group relative flex min-w-[240px] flex-shrink-0 items-center gap-3 rounded-lg px-3 py-2 transition"
                                        :class="perk.pivot?.is_default_step
                                            ? 'border border-sky-400/40 bg-sky-500/10'
                                            : 'border border-transparent bg-gray-800/40 opacity-60 hover:opacity-100'"
                                    >
                                        <img v-if="perkIcon(perk)" :src="perkIcon(perk)" :alt="perk.name" class="h-10 w-10 flex-shrink-0 rounded object-cover" />
                                        <div v-else class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded bg-gray-900 text-[10px] text-gray-500">?</div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-white">{{ perk.name }}</p>
                                            <p v-if="perk.description" class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-gray-400 group-hover:line-clamp-none">{{ perk.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                <img v-if="weapon?.ingame_image_path && !isVideoPath(weapon?.ingame_image_path)" :src="weapon.ingame_image_path" :alt="weapon.name" class="max-h-[90vh] max-w-[90vw] object-contain" @click.stop />
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

/*
 * États locaux : on ne lit PLUS weapon.perks depuis les props.
 * `perks` est chargé à la demande via l'endpoint
 * `/weapons/{hash}/perks` ajouté dans WeaponController::perks().
 * `perksLoading` permet d'afficher un skeleton cohérent avec le style du
 * drawer pendant le fetch.
 */
const perks = ref([]);
const perksLoading = ref(false);

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

/*
 * Fetch parallèle des perks (identique en cycle de vie au fetch des
 * traductions) — appelé depuis le même watcher sur props.weapon.
 * Endpoint : GET /weapons/{hash}/perks (cf. WeaponController::perks)
 *   → répond { perks: [{ id, hash, name, description, icon_url,
 *                        archive_icon_path, icon_downloaded,
 *                        pivot: { sort_order, column, node_index,
 *                                 is_default_step } }] }
 *
 * On garde une trace du dernier hash demandé pour éviter d'écraser
 * `perks` avec la réponse d'une arme précédente si l'utilisateur
 * clique très vite sur plusieurs cards (race condition).
 */
let perksRequestSeq = 0;
async function fetchPerks(hash) {
    if (!hash) { perks.value = []; perksLoading.value = false; return; }
    const seq = ++perksRequestSeq;
    perksLoading.value = true;
    try {
        const { data } = await axios.get(`/weapons/${hash}/perks`);
        // On ne conserve la réponse que si c'est bien la dernière
        // requête lancée (sinon on aurait un flash d'arme précédente).
        if (seq === perksRequestSeq) {
            perks.value = Array.isArray(data?.perks) ? data.perks : [];
        }
    } catch {
        if (seq === perksRequestSeq) perks.value = [];
    } finally {
        if (seq === perksRequestSeq) perksLoading.value = false;
    }
}

const otherLocales = computed(() => translations.value.filter((translation) => translation.locale !== props.weapon?.locale));

/*
 * Watcher principal : quand le weapon sélectionné change, on lance en
 * parallèle le fetch des traductions ET celui des perks. C'est ici
 * qu'on remplace l'ancienne logique qui lisait directement
 * props.weapon.perks.
 */
watch(() => props.weapon, (weapon) => {
    if (weapon?.hash) {
        fetchTranslations(weapon.hash);
        fetchPerks(weapon.hash);
    } else {
        translations.value = [];
        perks.value = [];
    }
    isMediaModalOpen.value = false;
}, { immediate: true });

watch(() => props.open, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
    if (!open) isMediaModalOpen.value = false;
});

function handleKeydown(event) {
    if (event.key !== 'Escape') return;
    if (isMediaModalOpen.value) closeMediaModal();
    else if (props.open) close();
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => { window.removeEventListener('keydown', handleKeydown); document.body.style.overflow = ''; });

function perkIcon(perk) {
    if (perk.icon_downloaded && perk.archive_icon_path) return perk.archive_icon_path;
    return perk.icon_url ?? null;
}

/*
 * Regroupement par colonne de perk (BucketOriginalIndex / "Column" côté
 * UI Bungie). Le backend renvoie déjà les perks triés par sort_order
 * puis hash ASC ; ici on regroupe en buckets `pivot.column` et on
 * retrie chaque bucket par `pivot.node_index` pour respecter l'ordre
 * d'affichage par colonne. Tri de clé de hash sur les clés : on
 * convertit en numérique pour ordonner "0", "1", "2", ...
 *
 * Source de vérité = `perks.value` (état local) — on ne lit PLUS jamais
 * `props.weapon.perks` (suppression validée par la contrainte du
 * cahier des charges).
 */
const perksByColumn = computed(() => {
    if (!perks.value?.length) return [];

    const groups = {};
    for (const perk of perks.value) {
        const col = perk.pivot?.column ?? 'default';
        if (!groups[col]) groups[col] = [];
        groups[col].push(perk);
    }

    return Object.entries(groups)
        // Tri par hash de la clé de colonne (les colonnes sont identifiées
        // par hash Bungie — donc tri stable via Number sur les hashs
        // numériques, fallback lexicographique).
        .sort(([a], [b]) => {
            const na = Number(a), nb = Number(b);
            if (!Number.isNaN(na) && !Number.isNaN(nb)) return na - nb;
            return String(a).localeCompare(String(b));
        })
        .map(([column, items]) => ({
            column,
            perks: items.slice().sort((a, b) => {
                const na = Number(a.pivot?.node_index ?? 0);
                const nb = Number(b.pivot?.node_index ?? 0);
                if (na !== nb) return na - nb;
                // Fallback : tri par hash en cas de node_index identique
                // (la raison d'être de ce tri par hash dans les échanges
                // précédents : correspondance stable entre armes et perks
                // traduits).
                return String(a.hash ?? '').localeCompare(String(b.hash ?? ''));
            }),
        }));
});
</script>

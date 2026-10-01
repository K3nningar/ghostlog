<template>
    <AppLayout :title="$t('grimoire.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-amber-300">{{ $t('layout.archive_index') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('grimoire.title') }}</h1>
                    <p class="mt-2 max-w-2xl text-sm text-white/50">{{ $t('grimoire.subtitle') }}</p>
                </div>
                <span class="text-xs text-white/40">{{ $t('layout.records', { count: entries.total }) }}</span>
            </div>

            <div class="mb-8 max-w-xl">
                <label for="grimoire-search" class="mb-1 block text-xs text-white/50">{{ $t('items.search_label') }}</label>
                <input
                    id="grimoire-search"
                    v-model="searchInput"
                    type="search"
                    :placeholder="$t('grimoire.search_placeholder')"
                    class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2.5 text-sm text-white outline-none focus:border-amber-300"
                />
            </div>

            <div v-if="entries.data.length" class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="entry in entries.data"
                    :key="entry.id"
                    class="group overflow-hidden rounded-xl border border-white/10 bg-white/[0.03] transition duration-200 hover:-translate-y-1 hover:border-amber-300/50 hover:bg-white/[0.06]"
                >
                    <button
                        type="button"
                        class="block w-full cursor-pointer text-left"
                        :aria-label="`${t('grimoire.read_entry')}: ${entryTitle(entry)}`"
                        @click="openEntry(entry)"
                    >
                        <div class="entry-thumbnail bg-black/30">
                            <img
                                v-if="entryImage(entry)"
                                :src="imageUrl(entryImage(entry))"
                                :alt="entryTitle(entry)"
                                loading="lazy"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                            />
                            <div v-else class="flex h-full items-center justify-center text-xs text-white/35">{{ $t('drawer.no_icon') }}</div>
                        </div>
                        <div class="p-5">
                            <p v-if="entry.category" class="mb-2 text-[10px] uppercase tracking-[0.2em] text-amber-300/80">{{ entry.category }}</p>
                            <h2 class="mb-3 text-lg font-medium text-white">{{ entryTitle(entry) }}</h2>
                            <p v-if="entry.description" class="line-clamp-3 text-sm leading-relaxed text-white/55">{{ stripMarkup(entry.description) }}</p>
                            <span class="mt-4 inline-flex border-t border-white/10 pt-3 text-xs uppercase tracking-[0.12em] text-white/60 transition group-hover:text-amber-200">{{ $t('grimoire.read_entry') }} <span aria-hidden="true" class="ml-2">↗</span></span>
                        </div>
                    </button>
                </article>
            </div>

            <p v-else class="rounded-xl border border-white/10 bg-white/[0.03] py-12 text-center text-white/45">{{ $t('grimoire.empty_state') }}</p>

            <nav v-if="entries.links.length > 3" class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="Pagination">
                <template v-for="(link, index) in entries.links" :key="index">
                    <span v-if="!link.url" class="rounded border border-white/5 px-3 py-2 text-xs text-white/25" v-html="linkLabel(link.label)" />
                    <button
                        v-else
                        type="button"
                        @click="goToPage(link.url)"
                        :class="[
                            'rounded border px-3 py-2 text-xs transition',
                            link.active ? 'border-amber-400 bg-amber-500/20 text-white' : 'border-white/10 text-white/70 hover:border-white/30 hover:text-white',
                        ]"
                        v-html="linkLabel(link.label)"
                    />
                </template>
            </nav>
        </div>

        <Teleport to="body">
            <div
                v-if="isModalOpen && selectedEntry"
                class="grimoire-modal"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="'grimoire-modal-title-' + selectedEntry.id"
                @click.self="closeEntry"
            >
                <div class="grimoire-modal__panel">
                    <button type="button" class="grimoire-modal__close" aria-label="Fermer" @click="closeEntry">×</button>
                    <div class="grimoire-modal__image-column">
                        <div class="grimoire-card-frame">
                            <img
                                v-if="modalImage(selectedEntry)"
                                :src="imageUrl(modalImage(selectedEntry))"
                                :alt="entryTitle(selectedEntry)"
                                class="grimoire-card-image"
                            />
                            <div v-else class="flex h-full items-center justify-center bg-neutral-900 text-sm text-white/40">{{ $t('drawer.no_icon') }}</div>
                        </div>
                    </div>
                    <div class="grimoire-modal__content">
                        <p class="mb-5 text-xs uppercase tracking-[0.22em] text-amber-300">Inventaire <span class="px-2 text-white/25">//</span> Grimoire</p>
                        <h2 :id="'grimoire-modal-title-' + selectedEntry.id" class="text-3xl font-semibold tracking-tight text-white sm:text-5xl">{{ entryTitle(selectedEntry) }}</h2>
                        <p v-if="entryIntro(selectedEntry)" class="mt-5 border-l-2 border-amber-400/70 pl-4 text-base italic leading-relaxed text-white/65" v-html="entryIntro(selectedEntry)" />
                        <div v-if="entryBody(selectedEntry)" class="grimoire-rich-text mt-8" v-html="entryBody(selectedEntry)" />
                        <p v-else class="mt-8 text-sm text-white/45">{{ $t('grimoire.empty_state') }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    entries: { type: Object, required: true },
    search: { type: String, default: '' },
});

const { t } = useI18n();
const searchInput = ref(props.search);
const selectedEntry = ref(null);
const isModalOpen = ref(false);
let searchTimer = null;

watch(searchInput, (value) => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/grimoire', { search: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

watch(isModalOpen, (open) => {
    if (open) {
        document.addEventListener('keydown', onKeydown);
        document.body.classList.add('grimoire-modal-open');
    } else {
        document.removeEventListener('keydown', onKeydown);
        document.body.classList.remove('grimoire-modal-open');
    }
});

onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
    document.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('grimoire-modal-open');
});

function openEntry(entry) {
    selectedEntry.value = entry;
    isModalOpen.value = true;
}

function closeEntry() {
    isModalOpen.value = false;
    selectedEntry.value = null;
}

function onKeydown(event) {
    if (event.key === 'Escape') closeEntry();
}

function goToPage(url) {
    closeEntry();
    router.visit(url, { preserveState: true, preserveScroll: true });
}

function linkLabel(label) {
    if (typeof label !== 'string') return label ?? '';
    if (label.includes('Previous')) return '&laquo; ' + t('items.prev_page');
    if (label.includes('Next')) return t('items.next_page') + ' &raquo;';
    return label;
}

function entryTitle(entry) {
    return entry.name || entry.cardName || 'Entrée du Grimoire';
}

function entryImage(entry) {
    return entry.image_normal || entry.image_normal_sm || entry.image_path || '';
}

function modalImage(entry) {
    return entry.image_hr || entry.image_normal || entry.image_hr_sm || entry.image_normal_sm || entry.image_path || '';
}

function entryIntro(entry) {
    return entry.intro || entry.cardIntro || '';
}

function entryBody(entry) {
    return entry.description || entry.content || '';
}

function stripMarkup(value) {
    return String(value).replace(/<[^>]*>/g, '').trim();
}

function imageUrl(path) {
    if (!path) return '';
    if (/^(https?:)?\/\//i.test(path)) return path;

    const normalized = path
        .replace(/^\/+/, '')
        .replace(/^storage\//i, '');

    if (normalized.startsWith('archive/')) return `/${normalized}`;
    if (normalized.startsWith('grimoire/')) return `/archive/${normalized}`;

    return `/archive/grimoire/${normalized}`;
}
</script>

<style scoped>
.entry-thumbnail {
    height: 188px;
    overflow: hidden;
}

.grimoire-modal {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-y: auto;
    padding: 1.5rem;
    background: rgba(3, 5, 8, 0.88);
    backdrop-filter: blur(7px);
}

.grimoire-modal__panel {
    position: relative;
    display: grid;
    grid-template-columns: minmax(250px, 0.82fr) minmax(0, 1.18fr);
    width: min(1120px, 100%);
    max-height: min(850px, calc(100vh - 3rem));
    overflow: auto;
    border: 1px solid rgba(255, 255, 255, 0.14);
    background: linear-gradient(135deg, #171b20 0%, #0c0f13 68%);
    box-shadow: 0 30px 100px rgba(0, 0, 0, 0.7);
}

.grimoire-modal__close {
    position: absolute;
    right: 1rem;
    top: 0.65rem;
    z-index: 2;
    width: 2.5rem;
    height: 2.5rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 2rem;
    line-height: 1;
    transition: color 0.2s, transform 0.2s;
}

.grimoire-modal__close:hover,
.grimoire-modal__close:focus-visible {
    color: #f7c948;
    transform: rotate(90deg);
}

.grimoire-modal__image-column {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 420px;
    padding: 3rem 2rem;
    background: radial-gradient(circle at center, rgba(95, 87, 65, 0.2), transparent 65%), #111418;
}

.grimoire-card-frame {
    width: min(100%, 390px);
    aspect-ratio: 4 / 5;
    overflow: hidden;
    border-radius: 3px;
    background: #dad7ce;
    box-shadow: 0 0 0 1px #f1d37b, 0 18px 38px rgba(0, 0, 0, 0.55);
}

.grimoire-card-image {
    display: block;
    width: 100%;
    height: 100%;
}

.grimoire-modal__content {
    min-width: 0;
    padding: 4.5rem clamp(1.5rem, 5vw, 4.5rem) 3.5rem 2.5rem;
}

.grimoire-rich-text {
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.98rem;
    line-height: 1.8;
}

.grimoire-rich-text :deep(p + p) { margin-top: 1rem; }
.grimoire-rich-text :deep(a) { color: #f7c948; text-decoration: underline; }
.grimoire-rich-text :deep(strong) { color: rgba(255, 255, 255, 0.95); }

@media (max-width: 700px) {
    .grimoire-modal { align-items: flex-start; padding: 0.75rem; }
    .grimoire-modal__panel { display: flex; flex-direction: column; max-height: calc(100vh - 1.5rem); }
    .grimoire-modal__image-column { min-height: 0; padding: 3.5rem 2rem 1.5rem; }
    .grimoire-card-frame { width: min(100%, 280px); }
    .grimoire-modal__content { padding: 1.5rem 1.5rem 2.5rem; }
}
</style>
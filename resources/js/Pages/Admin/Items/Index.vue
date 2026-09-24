<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

const { t } = useI18n();

const props = defineProps({
    items: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const locale = ref(props.filters.locale ?? '');

// Partial reloads : seule la liste (et les filtres) est rechargée.
const RELOAD_ONLY = ['items', 'filters'];

const loading = ref(false);

let timer = null;
watch(search, (value) => {
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => sendFilters(), 300);
});
watch([category, locale], () => {
    if (timer) clearTimeout(timer);
    sendFilters();
});

function sendFilters() {
    router.cancel();
    loading.value = true;
    router.get(
        '/admin/items',
        {
            search: search.value || undefined,
            category: category.value || undefined,
            locale: locale.value || undefined,
        },
        {
            only: RELOAD_ONLY,
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => (loading.value = false),
        }
    );
}

function goToPage(url) {
    if (!url) return;
    router.cancel();
    loading.value = true;
    router.visit(url, {
        only: RELOAD_ONLY,
        preserveState: true,
        preserveScroll: true,
        onFinish: () => (loading.value = false),
    });
}

function prefetch(url) {
    if (url) router.prefetch(url, { only: RELOAD_ONLY });
}

function linkLabel(label) {
    if (typeof label !== 'string') return label ?? '';
    if (label.includes('Previous')) return '&laquo; ' + t('items.prev_page');
    if (label.includes('Next')) return t('items.next_page') + ' &raquo;';
    return label;
}
</script>

<template>
    <AppLayout :title="$t('admin.title')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-[var(--dm-accent)]">{{ $t('admin.archive_administration') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('admin.title') }}</h1>
                </div>
                <a
                    href="/admin/items/create"
                    class="rounded-lg border border-[var(--dm-accent)]/50 bg-[var(--dm-accent)]/10 px-4 py-2 text-sm font-medium text-[var(--dm-accent)] transition hover:bg-[var(--dm-accent)]/20"
                >
                    + {{ $t('admin.new_item') }}
                </a>
            </div>

            <div v-if="$page.props.flash?.success" class="mb-4 rounded-lg border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm text-green-300">
                {{ $page.props.flash.success }}
            </div>

            <div class="mb-6 flex flex-wrap gap-3 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <div class="min-w-[220px] flex-1">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('admin.search_hash_name') }}</label>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="$t('admin.search_hash_name')"
                        class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-[var(--dm-accent)]"
                    />
                </div>
                <div class="min-w-[160px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('admin.category') }}</label>
                    <select v-model="category" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-[var(--dm-accent)]">
                        <option value="" class="bg-black text-white">{{ $t('items.confidentiality_all') }}</option>
                        <option v-for="cat in categories" :key="cat" :value="cat" class="bg-black text-white">{{ cat }}</option>
                    </select>
                </div>
                <div class="min-w-[120px]">
                    <label class="mb-1 block text-xs text-white/50">{{ $t('admin.locale') }}</label>
                    <select v-model="locale" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-[var(--dm-accent)]">
                        <option value="" class="bg-black text-white">{{ $t('items.confidentiality_all') }}</option>
                        <option v-for="loc in locales" :key="loc" :value="loc" class="bg-black text-white">{{ loc }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-white/10 bg-white/[0.03]">
                <div v-if="loading" class="h-0.5 w-full overflow-hidden">
                    <div class="h-full w-1/3 animate-pulse bg-[var(--dm-accent)]/60"></div>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-white/10 text-xs uppercase tracking-wide text-white/50">
                        <tr>
                            <th class="px-4 py-3">Hash</th>
                            <th class="px-4 py-3">{{ $t('admin.name') }}</th>
                            <th class="px-4 py-3">{{ $t('admin.locale') }}</th>
                            <th class="px-4 py-3">{{ $t('admin.category') }}</th>
                            <th class="px-4 py-3">{{ $t('items.rarity_label') }}</th>
                            <th class="px-4 py-3">{{ $t('admin.icon_downloaded') }}</th>
                            <th class="px-4 py-3">{{ $t('admin.in_game') }}</th>
                            <th class="px-4 py-3">{{ $t('admin.updated') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr v-for="item in items.data" :key="item.id" class="transition hover:bg-white/[0.04]">
                            <td class="px-4 py-2 font-mono text-xs text-white/70">{{ item.hash }}</td>
                            <td class="max-w-[260px] truncate px-4 py-2 text-white">{{ item.name ?? '—' }}</td>
                            <td class="px-4 py-2 text-white/70">{{ item.locale }}</td>
                            <td class="px-4 py-2 text-white/70">{{ item.category_slug ?? '—' }}</td>
                            <td class="px-4 py-2 text-white/70">{{ item.tier_type_name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span :class="item.icon_downloaded ? 'text-green-400' : 'text-red-400'">{{ item.icon_downloaded ? $t('admin.yes') : $t('admin.no') }}</span>
                            </td>
                            <td class="px-4 py-2">
                                <span :class="item.ingame_image_path ? 'text-green-400' : 'text-red-400'">{{ item.ingame_image_path ? $t('admin.yes') : $t('admin.no') }}</span>
                            </td>
                            <td class="px-4 py-2 text-xs text-white/50">{{ new Date(item.updated_at).toLocaleString() }}</td>
                            <td class="px-4 py-2 text-right">
                <a
                    :href="`/admin/items/${item.id}/edit`"
                    @mouseenter="router.prefetch($event.currentTarget.href)"
                    class="rounded border border-[var(--dm-accent)]/40 bg-[var(--dm-accent)]/10 px-3 py-1 text-xs text-[var(--dm-accent)] transition hover:bg-[var(--dm-accent)]/20"
                >
                                    {{ $t('admin.edit') }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-if="items.data.length === 0" class="py-12 text-center text-white/40">{{ $t('admin.no_items') }}</p>

            <nav v-if="items.links && items.links.length > 3" class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
                <template v-for="(link, index) in items.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="cursor-not-allowed rounded border border-white/5 px-3 py-1 text-xs text-white/30"
                        v-html="linkLabel(link.label)"
                    />
                    <button
                        v-else
                        @click="goToPage(link.url)"
                        @mouseenter="prefetch(link.url)"
                        :class="[
                            'rounded border px-3 py-1 text-xs transition',
                            link.active
                                ? 'border-[var(--dm-accent)] bg-[var(--dm-accent)]/20 text-white'
                                : 'border-white/10 text-white/70 hover:border-white/30 hover:text-white',
                        ]"
                        v-html="linkLabel(link.label)"
                    />
                </template>
            </nav>
        </div>
    </AppLayout>
</template>

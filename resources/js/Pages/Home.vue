<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    stats: Object,
});

const categories = [
    { key: 'weapons', image: '/img/menus/weapons.jpg' },
    { key: 'armors', image: '/img/menus/armors.jpg' },
    { key: 'ships', image: '/img/menus/ships.jpg' },
    { key: 'sparrows', image: '/img/menus/sparrows.jpg' },
    { key: 'emblems', image: '/img/menus/emblems.jpg' },
    { key: 'consumables', image: '/img/menus/consumables.jpg' },
];

const categoryPaths = {
    weapons: '/weapons',
    armors: '/armors',
    ships: '/ships',
    sparrows: '/sparrows',
    emblems: '/emblems',
    consumables: '/consumables',
};
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-[var(--dm-background)] text-[var(--dm-text)]">

            <!-- Hero -->
            <section class="mx-auto max-w-7xl px-6 pb-20 pt-20 sm:pt-28">
                <p class="mb-5 text-xs uppercase tracking-[0.28em] text-sky-300">Destiny 1 archive</p>
                <h1 class="max-w-4xl text-5xl font-medium leading-[0.98] tracking-tight text-white sm:text-7xl">
                    D1 Ghost<span class="text-sky-300">Log</span>
                </h1>
                <p class="mt-8 max-w-2xl text-base leading-8 text-white/60 sm:text-lg">
                    {{ $t('layout.app_description') }} {{ $t('layout.app_description_line2') }}
                </p>
            </section>

            <!-- Categories -->
            <section class="mx-auto max-w-7xl px-6 pb-24">
                <div class="mb-5 flex items-end justify-between border-b border-white/10 pb-4">
                    <h2 class="text-xs uppercase tracking-[0.24em] text-white/50">{{ $t('layout.archive_index') }}</h2>
                    <span class="text-xs text-white/35">{{ $t('layout.records', { count: Object.values(stats ?? {}).reduce((total, count) => total + count, 0) }) }}</span>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="category in categories"
                        :key="category.key"
                        :href="categoryPaths[category.key] ?? '#'"
                        class="group relative flex h-48 flex-col justify-end overflow-hidden rounded-xl border border-white/10 bg-white/[0.03] p-6 transition hover:-translate-y-1 hover:border-[var(--dm-accent)]/60"
                    >
                        <img
                            :src="category.image"
                            :alt="$t(`categories.${category.key}`)"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 h-full w-full object-cover opacity-80 transition group-hover:opacity-100"
                        />
                        <!-- Overlay sombre pour la lisibilité -->
                        <div class="absolute inset-0 bg-black/65 transition group-hover:bg-black/45"></div>

                        <!-- Contenu -->
                        <div class="relative z-10">
                            <h3 class="mb-1 text-lg font-semibold text-white transition group-hover:text-sky-300">
                                {{ $t(`categories.${category.key}`) }}
                            </h3>
                            <p class="text-sm text-white/55">
                                {{ $t('layout.archived_items', { count: stats[category.key] ?? 0 }) }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Footer -->
            <footer class="border-t border-white/10 py-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-6 text-xs text-white/40 sm:flex-row sm:items-center sm:justify-between">
                    <p>{{ $t('layout.footer_copyright') }}</p>
                    <p>{{ $t('layout.footer_copyright_bungie') }}</p>
                </div>
            </footer>
        </div>
    </AppLayout>
</template>
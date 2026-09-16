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
        <div class="min-h-screen bg-gray-950 text-gray-100">

            <!-- Hero -->
            <section class="max-w-7xl mx-auto px-6 py-24 text-center">
                <h1 class="text-5xl font-bold mb-6">
                    D1 Ghost<span class="text-sky-400">Log</span>
                </h1>
                <p class="text-gray-400 max-w-4xl mx-auto text-lg">
                    {{ $t('layout.app_description') }} <br><br>{{ $t('layout.app_description_line2') }}
                </p>
            </section>

            <!-- Categories -->
            <section class="max-w-7xl mx-auto px-6 pb-24">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <Link
                        v-for="category in categories"
                        :key="category.key"
                        :href="categoryPaths[category.key] ?? '#'"
                        class="group relative overflow-hidden border border-gray-800 rounded-xl p-8 text-center hover:border-sky-400/50 transition h-48 flex flex-col justify-end"
                        :style="{
                            backgroundImage: `url(${category.image})`,
                            backgroundSize: 'cover',
                            backgroundPosition: 'center',
                        }"
                    >
                        <!-- Overlay sombre pour la lisibilité -->
                        <div class="absolute inset-0 bg-black/60 group-hover:bg-black/40 transition"></div>

                        <!-- Contenu -->
                        <div class="relative z-10">
                            <h3 class="text-lg font-semibold mb-1 text-white group-hover:text-sky-400 transition">
                                {{ $t(`categories.${category.key}`) }}
                            </h3>
                            <p class="text-sm text-gray-300">
                                {{ $t('layout.archived_items', { count: stats[category.key] ?? 0 }) }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Footer -->
            <footer class="border-t border-gray-800 py-8">
                <div class="max-w-7xl mx-auto px-6 flex items-center justify-between text-sm text-gray-500">
                    <p>{{ $t('layout.footer_copyright') }}</p>
                    <p>{{ $t('layout.footer_copyright_bungie') }}</p>
                </div>
            </footer>
        </div>
    </AppLayout>
</template>
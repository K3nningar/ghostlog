<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { i18n } from '@/i18n';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();

const currentLocale = computed(() => page.props.currentLocale);
const availableLocales = computed(() => page.props.availableLocales ?? {});

// Destiny locale mapping
function syncI18nLocale(locale) {
    if (i18n.global.availableLocales.includes(locale)) {
        i18n.global.locale.value = locale;
        localStorage.setItem('app_locale', locale);
    } else {
        // fallback si le code ne matche pas exactement (ex: "en-us" -> "en")
        const short = locale.split('-')[0];
        if (i18n.global.availableLocales.includes(short)) {
            i18n.global.locale.value = short;
            localStorage.setItem('app_locale', short);
        }
    }
}

// Loading sync
watch(currentLocale, (newLocale) => {
    if (newLocale) {
        syncI18nLocale(newLocale);
    }
}, { immediate: true });

function changeLocale(event) {
    const locale = event.target.value;

    // UI sync
    syncI18nLocale(locale);

    router.post('/locale', { locale }, {
        preserveScroll: true,
        preserveState: false,
    });
}
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-gray-950">
        <header class="flex items-center justify-between gap-2 border-b border-gray-800 bg-gray-900 px-6 py-3">
            <!-- Logo to the left -->
            <Link href="/" class="flex items-center gap-3">
                <div class="flex items-center gap-3">
                    <img src="/img/logo/logo.png" alt="GhostLog Logo" class="w-10 h-10" />
                    <span class="text-xl font-semibold tracking-wide text-white">
                        D1 Ghost<span class="text-sky-400">Log</span>
                    </span>
                </div>
            </Link>    
            <!-- Language selector to the right -->
            <div class="flex items-center gap-2">
                <label class="text-xs text-gray-400" for="locale-select">{{ $t('layout.language') }}</label>
                <select
                    id="locale-select"
                    :value="currentLocale"
                    @change="changeLocale"
                    class="rounded border border-gray-700 bg-gray-800 px-2 py-1 text-sm text-white focus:border-blue-400 focus:outline-none"
                >
                    <option
                        v-for="(label, code) in availableLocales"
                        :key="code"
                        :value="code"
                    >
                        {{ label }}
                    </option>
                </select>
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
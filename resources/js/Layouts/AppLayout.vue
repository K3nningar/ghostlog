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

    <div class="min-h-screen bg-[var(--dm-background)] text-[var(--dm-text)]">
        <header class="sticky top-0 z-30 border-b border-white/10 bg-[#101010]/90 px-5 py-4 backdrop-blur-xl sm:px-8">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                <Link href="/" class="group flex items-center gap-3">
                    <img src="/img/logo/logo.png" alt="GhostLog Logo" class="h-9 w-9 rounded-lg opacity-90 transition group-hover:opacity-100" />
                    <span class="text-base font-semibold tracking-[0.18em] text-white sm:text-lg">
                        D1 <span class="text-[var(--dm-accent)]">GhostLog</span>
                    </span>
                </Link>
                <nav class="hidden items-center gap-6 text-xs uppercase tracking-[0.16em] text-white/50 md:flex">
                    <Link href="/" class="transition hover:text-white">{{ $t('layout.archive') }}</Link>
                    <Link href="/ships" class="transition hover:text-white">{{ $t('categories.ships') }}</Link>
                    <Link href="/sparrows" class="transition hover:text-white">{{ $t('categories.sparrows') }}</Link>
                    <Link href="/emblems" class="transition hover:text-white">{{ $t('categories.emblems') }}</Link>
                </nav>
                <div class="flex items-center gap-2">
                    <span class="hidden text-[10px] uppercase tracking-[0.16em] text-white/30 sm:inline">
                        v{{ $page.props.appVersion }}
                    </span>
                    <label class="sr-only" for="locale-select">{{ $t('layout.language') }}</label>
                <select
                    id="locale-select"
                    :value="currentLocale"
                    @change="changeLocale"
                    class="rounded-lg border border-white/10 bg-[#151515] px-2 py-1.5 text-xs text-white outline-none transition focus:border-[var(--dm-accent)]"
                    style="color-scheme: dark;"
                >
                    <option
                        v-for="(label, code) in availableLocales"
                        :key="code"
                        :value="code"
                        class="bg-[#151515] text-white"
                    >
                        {{ label }}
                    </option>
                </select>
                </div>
            </div>
        </header>

        <main class="relative overflow-hidden">
            <slot />
        </main>
        <footer class="flex flex-col gap-2 border-t border-white/10 px-5 py-5 text-center text-xs text-white/35 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <span>{{ $t('layout.footer_copyright') }}</span>
            <span>v{{ $page.props.appVersion }}</span>
            <span>{{ $t('layout.footer_copyright_bungie') }}</span>
        </footer>
    </div>
</template>
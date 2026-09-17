<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[var(--dm-background)] px-5 pt-6 text-[var(--dm-text)] sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-[var(--dm-accent)]" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] px-6 py-5 shadow-2xl shadow-black/20 sm:max-w-md"
        >
            <slot />
        </div>
        <footer class="mt-6 pb-6 text-xs text-white/35">
            v{{ $page.props.appVersion }}
        </footer>
    </div>
</template>

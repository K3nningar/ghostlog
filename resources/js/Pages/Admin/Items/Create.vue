<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ItemForm from '@/Components/Admin/ItemForm.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout :title="$t('admin.new_item')">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-[var(--dm-accent)]">{{ $t('admin.archive_administration') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ $t('admin.new_item') }}</h1>
                </div>
                <a href="/admin/items" class="text-sm text-white/50 underline transition hover:text-white">&larr; {{ $t('admin.back_to_list') }}</a>
            </div>

            <ItemForm
                mode="create"
                :categories="categories"
                :locales="locales"
            />
        </div>
    </AppLayout>
</template>

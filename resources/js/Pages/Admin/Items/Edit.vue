<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ItemForm from '@/Components/Admin/ItemForm.vue';

defineProps({
    item: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout :title="item.name ?? item.hash">
        <div class="mx-auto max-w-7xl px-5 py-10 text-[var(--dm-text)] sm:px-8">
            <div class="mb-8 flex items-end justify-between gap-4 border-b border-white/10 pb-5">
                <div>
                    <p class="mb-2 text-xs uppercase tracking-[0.24em] text-[var(--dm-accent)]">{{ $t('admin.archive_administration') }}</p>
                    <h1 class="text-3xl font-medium tracking-tight text-white sm:text-4xl">{{ item.name ?? item.hash }}</h1>
                </div>
                <a href="/admin/items" class="text-sm text-white/50 underline transition hover:text-white">&larr; {{ $t('admin.back_to_list') }}</a>
            </div>

            <ItemForm
                mode="edit"
                :categories="categories"
                :locales="locales"
                :item="item"
            />
        </div>
    </AppLayout>
</template>

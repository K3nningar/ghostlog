<script setup>
import { computed, reactive, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

/*
 * Fichiers sélectionnés (non envoyés par useForm) : gardés à part puis
 * passés au FormData final lors du submit.
 */
const files = reactive({
    archive_icon_file: null,
    archive_icon_secondary_file: null,
    ingame_media_file: null,
});

function onFileChange(event, key) {
    // L'icône secondaire n'existe que pour les emblèmes.
    if (key === 'archive_icon_secondary_file' && form.category_slug !== 'emblems') {
        files[key] = null;
        event.target.value = '';
        return;
    }
    files[key] = event.target.files?.[0] ?? null;
}

const isSecondaryUploadAllowed = computed(() => form.category_slug === 'emblems');

function isVideo(path) {
    return /\.(mp4|webm|ogg|ogv|mov|m4v)(?:$|[?#])/i.test(path);
}

/*
 * Resynchronisation du formulaire quand l'item change (après une mise à
 * jour réussie) : useForm n'initialise les champs qu'au montage, et ne
 * suit pas les props rafraîchies. Sans ce watcher, les valeurs renvoyées
 * par le serveur (ex. chemin d'upload relatif) ne s'afficheraient pas et
 * un second enregistrement écraserait les nouveaux chemins avec les
 * anciennes valeurs du formulaire.
 */
watch(() => props.item, (item) => {
    if (isEdit.value === false || !item) return;

    form.hash = item.hash ?? '';
    form.locale = item.locale ?? 'fr';
    form.name = item.name ?? '';
    form.description = item.description ?? '';
    form.icon_url = item.icon_url ?? '';
    form.item_type = item.item_type ?? '';
    form.item_type_name = item.item_type_name ?? '';
    form.item_sub_type = item.item_sub_type ?? '';
    form.class_type = item.class_type ?? '';
    form.tier_type = item.tier_type ?? '';
    form.tier_type_name = item.tier_type_name ?? '';
    form.bucket_type_hash = item.bucket_type_hash ?? '';
    form.category_hashes = Array.isArray(item.category_hashes) ? item.category_hashes.join(', ') : '';
    form.category_slug = item.category_slug ?? '';
    form.subcategory_slug = item.subcategory_slug ?? '';
    form.archive_icon_path = item.archive_icon_path ?? '';
    form.archive_icon_path_secondary = item.archive_icon_path_secondary ?? '';
    form.ingame_image_path = item.ingame_image_path ?? '';
    form.icon_downloaded = Boolean(item.icon_downloaded);
    form.raw_json = item.raw_json ? JSON.stringify(item.raw_json, null, 2) : '{}';
});

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    subcategories: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    hash: props.item?.hash ?? '',
    locale: props.item?.locale ?? 'fr',
    name: props.item?.name ?? '',
    description: props.item?.description ?? '',
    icon_url: props.item?.icon_url ?? '',
    item_type: props.item?.item_type ?? '',
    item_type_name: props.item?.item_type_name ?? '',
    item_sub_type: props.item?.item_sub_type ?? '',
    class_type: props.item?.class_type ?? '',
    tier_type: props.item?.tier_type ?? '',
    tier_type_name: props.item?.tier_type_name ?? '',
    bucket_type_hash: props.item?.bucket_type_hash ?? '',
    category_hashes: Array.isArray(props.item?.category_hashes) ? props.item.category_hashes.join(', ') : '',
    category_slug: props.item?.category_slug ?? '',
    subcategory_slug: props.item?.subcategory_slug ?? '',
    archive_icon_path: props.item?.archive_icon_path ?? '',
    archive_icon_path_secondary: props.item?.archive_icon_path_secondary ?? '',
    ingame_image_path: props.item?.ingame_image_path ?? '',
    icon_downloaded: Boolean(props.item?.icon_downloaded),
    raw_json: props.item?.raw_json ? JSON.stringify(props.item.raw_json, null, 2) : '{}',
});

const numericFields = [
    { key: 'item_type', label: 'Item type' },
    { key: 'item_sub_type', label: 'Item sub type' },
    { key: 'class_type', label: 'Class type' },
    { key: 'bucket_type_hash', label: 'Bucket type hash' },
];

/*
 * Rareté : identifiants Bungie D1 (2=Commun, 3=Peu commun, 4=Rare,
 * 5=Légendaire, 6=Exotique). Le libellé est traduit selon la LOCALE DE
 * L'OBJET (form.locale), pas la langue de l'interface.
 */
const rarityOptions = [
    { value: 2, key: 'items.rarity_basic' },
    { value: 3, key: 'items.rarity_uncommon' },
    { value: 4, key: 'items.rarity_rare' },
    { value: 5, key: 'items.rarity_legendary' },
    { value: 6, key: 'items.rarity_exotic' },
];

const rarityLabel = (key) => t(key, {}, { locale: form.locale || undefined });

const rarityValue = computed({
    get: () => (form.tier_type === '' || form.tier_type === null ? '' : Number(form.tier_type)),
    set: (value) => {
        form.tier_type = value === '' ? '' : value;
        const option = rarityOptions.find((o) => o.value === Number(value));
        form.tier_type_name = option ? rarityLabel(option.key) : '';
    },
});

// Si la locale de l'objet change, retraduire le nom du tier déjà choisi.
watch(() => form.locale, () => {
    const option = rarityOptions.find((o) => o.value === Number(form.tier_type));
    if (option) form.tier_type_name = rarityLabel(option.key);
});

function submit() {
    const payload = { ...form.data() };

    // category_hashes : chaîne "1, 20, 39" → tableau d'entiers
    payload.category_hashes = String(payload.category_hashes)
        .split(',')
        .map((part) => part.trim())
        .filter((part) => part !== '')
        .map((part) => Number(part))
        .filter((n) => !Number.isNaN(n));

    if (payload.category_hashes.length === 0) delete payload.category_hashes;

    const hasFiles = Object.values(files).some(Boolean);

    const options = { preserveScroll: true };

    if (hasFiles) {
        // Upload de fichiers → multipart obligatoire (FormData). Inertia ne
        // spooﬁe pas la méthode sur un FormData : on POSTe toujours, avec le
        // champ Laravel standard `_method=PUT` pour l'édition.
        const url = isEdit.value ? `/admin/items/${props.item.id}` : '/admin/items';
        const data = new FormData();
        if (isEdit.value) data.append('_method', 'PUT');

        for (const [key, value] of Object.entries(payload)) {
            if (value === null || value === undefined) continue;
            if (Array.isArray(value)) {
                for (const entry of value) data.append(`${key}[]`, String(entry));
            } else if (typeof value === 'boolean') {
                data.append(key, value ? '1' : '0');
            } else {
                data.append(key, value);
            }
        }

        for (const [key, file] of Object.entries(files)) {
            if (file) data.append(key, file);
        }

        options.forceFormData = true;
        options.onSuccess = () => {
            files.archive_icon_file = null;
            files.archive_icon_secondary_file = null;
            files.ingame_media_file = null;
        };

        form.transform(() => data).submit('post', url, options);

        return;
    }

    if (isEdit.value) {
        form
            .transform(() => payload)
            .put(`/admin/items/${props.item.id}`, options);
    } else {
        form
            .transform(() => payload)
            .post('/admin/items', options);
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-8">
        <div v-if="form.hasErrors" class="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <ul class="list-disc pl-5">
                <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
            </ul>
        </div>
        <div v-if="$page.props.flash?.success" class="rounded-lg border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm text-green-300">
            {{ $page.props.flash.success }}
        </div>

        <!-- Identité -->
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-white/60">Identité</legend>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-white/50">Hash *</label>
                    <input v-model="form.hash" type="text" required class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-white/50">Locale *</label>
                    <select v-model="form.locale" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option v-for="loc in locales" :key="loc" :value="loc" class="bg-black text-white">{{ loc }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-white/50">Nom</label>
                    <input v-model="form.name" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-white/50">Description</label>
                    <textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300"></textarea>
                </div>
            </div>
        </fieldset>

        <!-- Types Bungie -->
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-white/60">Types Bungie</legend>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in numericFields" :key="field.key">
                    <label class="mb-1 block text-xs text-white/50">{{ field.label }}</label>
                    <input v-model="form[field.key]" type="number" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-white/50">Item type name</label>
                    <input v-model="form.item_type_name" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>
                <div>
                    <label class="mb-1 block text-xs text-white/50">Rareté (tier_type + nom, traduit selon la locale de l'objet : {{ form.locale }})</label>
                    <select v-model="rarityValue" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="" class="bg-black text-white">—</option>
                        <option v-for="option in rarityOptions" :key="option.value" :value="option.value" class="bg-black text-white">
                            {{ rarityLabel(option.key) }} ({{ option.value }})
                        </option>
                    </select>
                </div>
            </div>
        </fieldset>

        <!-- Classification -->
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-white/60">Classification</legend>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-white/50">Catégorie (slug)</label>
                    <select v-model="form.category_slug" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="" class="bg-black text-white">—</option>
                        <option v-for="cat in categories" :key="cat" :value="cat" class="bg-black text-white">{{ cat }}</option>
                        <option v-if="form.category_slug && !categories.includes(form.category_slug)" :value="form.category_slug" class="bg-black text-white">{{ form.category_slug }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs text-white/50">Sous-catégorie (slug)</label>
                    <select v-model="form.subcategory_slug" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300">
                        <option value="" class="bg-black text-white">—</option>
                        <option v-for="sub in subcategories" :key="sub" :value="sub" class="bg-black text-white">{{ sub }}</option>
                        <option v-if="form.subcategory_slug && !subcategories.includes(form.subcategory_slug)" :value="form.subcategory_slug" class="bg-black text-white">{{ form.subcategory_slug }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs text-white/50">Category hashes (séparés par des virgules)</label>
                    <input v-model="form.category_hashes" type="text" placeholder="1, 20, 39" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>
            </div>
        </fieldset>

        <!-- Médias -->
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-white/60">Médias</legend>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="mb-1 block text-xs text-white/50">Icon URL (Bungie CDN)</label>
                    <input v-model="form.icon_url" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>

                <div class="rounded-lg border border-white/10 bg-black/10 p-4">
                    <label class="mb-1 block text-xs font-semibold text-white/70">Icône principale</label>
                    <div class="flex flex-wrap items-center gap-4">
                        <img
                            v-if="form.archive_icon_path"
                            :src="`/${form.archive_icon_path}`"
                            alt="Icône actuelle"
                            class="h-16 w-16 rounded-lg border border-white/10 object-cover"
                        />
                        <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg border border-white/10 bg-black/20 text-xs text-white/30">—</div>
                        <div class="min-w-[220px] flex-1">
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" @change="onFileChange($event, 'archive_icon_file')" class="w-full text-xs text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-500/20 file:px-3 file:py-2 file:text-xs file:font-medium file:text-sky-300 hover:file:bg-sky-500/30" />
                            <p v-if="files.archive_icon_file" class="mt-1 text-xs text-green-400">Fichier sélectionné : {{ files.archive_icon_file.name }} (écrasera le champ à l'enregistrement)</p>
                        </div>
                    </div>
                    <label class="mt-3 mb-1 block text-xs text-white/50">Chemin local (relatif à public/, auto-rempli par l'upload)</label>
                    <input v-model="form.archive_icon_path" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>

                <div class="rounded-lg border border-white/10 bg-black/10 p-4">
                    <label class="mb-1 block text-xs font-semibold text-white/70">Icône secondaire (emblèmes)</label>
                    <div class="flex flex-wrap items-center gap-4">
                        <img
                            v-if="form.archive_icon_path_secondary"
                            :src="`/${form.archive_icon_path_secondary}`"
                            alt="Icône secondaire actuelle"
                            class="h-16 w-16 rounded-lg border border-white/10 object-cover"
                        />
                        <div v-else class="flex h-16 w-16 items-center justify-center rounded-lg border border-white/10 bg-black/20 text-xs text-white/30">—</div>
                        <div class="min-w-[220px] flex-1">
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp" :disabled="!isSecondaryUploadAllowed" @change="onFileChange($event, 'archive_icon_secondary_file')" class="w-full text-xs text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-500/20 file:px-3 file:py-2 file:text-xs file:font-medium file:text-sky-300 hover:file:bg-sky-500/30 disabled:cursor-not-allowed disabled:opacity-50" />
                            <p v-if="!isSecondaryUploadAllowed" class="mt-1 text-xs text-amber-400">Réservé à la catégorie « emblems ».</p>
                            <p v-else-if="files.archive_icon_secondary_file" class="mt-1 text-xs text-green-400">Fichier sélectionné : {{ files.archive_icon_secondary_file.name }} (écrasera le champ à l'enregistrement)</p>
                        </div>
                    </div>
                    <label class="mt-3 mb-1 block text-xs text-white/50">Chemin local (relatif à public/)</label>
                    <input v-model="form.archive_icon_path_secondary" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>

                <div class="rounded-lg border border-white/10 bg-black/10 p-4">
                    <label class="mb-1 block text-xs font-semibold text-white/70">Média en jeu (image ou vidéo)</label>
                    <div class="flex flex-wrap items-center gap-4">
                        <video v-if="form.ingame_image_path && isVideo(form.ingame_image_path)" :src="`/${form.ingame_image_path}`" class="h-24 w-40 rounded-lg border border-white/10 object-cover" muted loop autoplay playsinline></video>
                        <img v-else-if="form.ingame_image_path" :src="`/${form.ingame_image_path}`" alt="Média actuel" class="h-24 w-40 rounded-lg border border-white/10 object-cover" />
                        <div v-else class="flex h-24 w-40 items-center justify-center rounded-lg border border-white/10 bg-black/20 text-xs text-white/30">—</div>
                        <div class="min-w-[220px] flex-1">
                            <input type="file" accept="image/png,image/jpeg,image/gif,image/webp,video/mp4,video/webm,video/quicktime" @change="onFileChange($event, 'ingame_media_file')" class="w-full text-xs text-white/70 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-500/20 file:px-3 file:py-2 file:text-xs file:font-medium file:text-sky-300 hover:file:bg-sky-500/30" />
                            <p v-if="files.ingame_media_file" class="mt-1 text-xs text-green-400">Fichier sélectionné : {{ files.ingame_media_file.name }} (écrasera le champ à l'enregistrement)</p>
                        </div>
                    </div>
                    <label class="mt-3 mb-1 block text-xs text-white/50">Chemin local (relatif à public/, auto-rempli par l'upload)</label>
                    <input v-model="form.ingame_image_path" type="text" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 text-sm text-white outline-none focus:border-sky-300" />
                </div>

                <label class="flex items-center gap-2 text-sm text-white">
                    <input v-model="form.icon_downloaded" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-black/20" />
                    Icône téléchargée en local (cochée automatiquement après un upload d'icône principale)
                </label>
            </div>
        </fieldset>

        <!-- JSON brut -->
        <fieldset class="rounded-xl border border-white/10 bg-white/[0.03] p-5">
            <legend class="px-2 text-xs font-semibold uppercase tracking-wide text-white/60">JSON brut du manifest</legend>
            <textarea v-model="form.raw_json" rows="12" class="w-full rounded-lg border border-white/10 bg-black/20 px-3 py-2 font-mono text-xs text-white outline-none focus:border-sky-300"></textarea>
            <p class="mt-2 text-xs text-white/40">JSON validé à l'enregistrement : en cas de syntaxe invalide, la valeur existante est conservée.</p>
        </fieldset>

        <div class="flex items-center justify-end gap-3">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-lg bg-sky-500 px-5 py-2 text-sm font-medium text-white transition hover:bg-sky-400 disabled:opacity-50"
            >
                {{ form.processing ? 'Enregistrement...' : (isEdit ? 'Mettre à jour' : 'Créer l\'item') }}
            </button>
        </div>
    </form>
</template>

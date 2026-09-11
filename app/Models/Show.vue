<script setup>
import { Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    item: Object,
})

const classTypeNames = {
    0: 'Titan',
    1: 'Chasseur',
    2: 'Chasseresse',
    3: 'Sans classe',
}

const className = computed(() => {
    if (props.item.class_type === null || props.item.class_type === undefined) return null
    return classTypeNames[props.item.class_type] ?? `Classe inconnue (${props.item.class_type})`
})

const rawData = computed(() => props.item.raw_json ?? null)

const stats = computed(() => {
    if (!rawData.value?.stats) return []
    return Object.values(rawData.value.stats)
})

const perkHashes = computed(() => rawData.value?.perkHashes ?? [])
const categoryHashes = computed(() => rawData.value?.itemCategoryHashes ?? props.item.category_hashes ?? [])
const sourceHashes = computed(() => rawData.value?.sourceHashes ?? [])

const equippingBlock = computed(() => rawData.value?.equippingBlock ?? null)

const showRawJson = ref(false)

const tierColorClass = computed(() => {
    const map = {
        'De base': 'border-gray-400 text-gray-300',
        'Ordinaire': 'border-gray-400 text-gray-300',
        'Peu commun': 'border-green-500 text-green-400',
        'Rare': 'border-blue-500 text-blue-400',
        'Légendaire': 'border-purple-500 text-purple-400',
        'Exotique': 'border-yellow-500 text-yellow-400',
    }
    return map[props.item.tier_type_name] ?? 'border-gray-400 text-gray-300'
})

const backUrl = computed(() => {
    const saved = sessionStorage.getItem('items_index_query')
    return saved ? `/items?${saved}` : '/items'
})
</script>

<template>
    <div class="min-h-screen bg-gray-950 text-gray-100 p-6">
        <div class="max-w-5xl mx-auto">
            <Link
                :href="backUrl"
                class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-6 transition"
            >
                ← Retour à la liste
            </Link>

            <div class="bg-gray-900 rounded-xl border" :class="tierColorClass" style="border-width: 1px;">
                <div class="flex flex-col sm:flex-row gap-6 p-6">
                    <div class="flex-shrink-0">
                        <img
                            v-if="item.icon_url"
                            :src="item.icon_url"
                            :alt="item.name"
                            class="w-32 h-32 rounded-lg object-cover bg-gray-800 border"
                            :class="tierColorClass"
                        />
                        <div
                            v-else
                            class="w-32 h-32 rounded-lg bg-gray-800 flex items-center justify-center text-gray-600 text-xs"
                        >
                            Pas d'icône
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap mb-1">
                            <span class="text-xs uppercase tracking-wide font-semibold" :class="tierColorClass">
                                {{ item.tier_type_name }}
                            </span>
                            <span v-if="className" class="text-xs px-2 py-0.5 rounded bg-gray-800 text-gray-300">
                                {{ className }}
                            </span>
                        </div>

                        <h1 class="text-2xl font-bold text-white mb-2">
                            {{ item.name ?? 'Nom inconnu' }}
                        </h1>

                        <p v-if="item.description" class="text-gray-400 text-sm italic mb-4">
                            {{ item.description }}
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500 block text-xs">Type</span>
                                <span class="text-gray-200">{{ item.item_type_name ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-xs">Catégorie</span>
                                <span class="text-gray-200">{{ item.category_slug ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-xs">Sous-catégorie</span>
                                <span class="text-gray-200">{{ item.subcategory_slug ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-xs">Hash</span>
                                <span class="text-gray-200 font-mono">{{ item.hash }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-xs">Bucket hash</span>
                                <span class="text-gray-200 font-mono">{{ item.bucket_type_hash ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block text-xs">Locale</span>
                                <span class="text-gray-200 uppercase">{{ item.locale ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="stats.length" class="mt-6 bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Statistiques</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div
                        v-for="stat in stats"
                        :key="stat.statHash"
                        class="bg-gray-800 rounded-lg p-3"
                    >
                        <span class="text-gray-500 text-xs block font-mono">Hash: {{ stat.statHash }}</span>
                        <span class="text-white font-semibold text-lg">{{ stat.value }}</span>
                        <span class="text-gray-500 text-xs ml-1">
                            ({{ stat.minimum }}–{{ stat.maximum }})
                        </span>
                    </div>
                </div>
                <p class="text-xs text-gray-600 mt-3">
                    Les identifiants de statistiques ne sont pas encore traduits (nécessite le manifeste Bungie).
                </p>
            </div>

            <!-- Perks -->
            <div v-if="perkHashes.length" class="mt-6 bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Perks</h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="perk in perkHashes"
                        :key="perk"
                        class="px-3 py-1 bg-gray-800 rounded-full text-xs font-mono text-gray-300"
                    >
                        {{ perk }}
                    </span>
                </div>
            </div>

            <!-- Sources -->
            <div v-if="sourceHashes.length" class="mt-6 bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Sources</h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="src in sourceHashes"
                        :key="src"
                        class="px-3 py-1 bg-gray-800 rounded-full text-xs font-mono text-gray-300"
                    >
                        {{ src }}
                    </span>
                </div>
            </div>

            <!-- Categories -->
            <div v-if="categoryHashes.length" class="mt-6 bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Catégories (hash)</h2>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="cat in categoryHashes"
                        :key="cat"
                        class="px-3 py-1 bg-gray-800 rounded-full text-xs font-mono text-gray-300"
                    >
                        {{ cat }}
                    </span>
                </div>
            </div>

            <!-- Equipping block -->
            <div v-if="equippingBlock" class="mt-6 bg-gray-900 rounded-xl border border-gray-800 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Détails d'équipement</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                    <div>
                        <span class="text-gray-500 block text-xs">Slot d'équipement</span>
                        <span class="text-gray-200 font-mono">{{ equippingBlock.equipmentSlotHash ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Arrangement art</span>
                        <span class="text-gray-200">{{ equippingBlock.gearArtArrangementIndex ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 block text-xs">Weapon pattern hash</span>
                        <span class="text-gray-200 font-mono">{{ equippingBlock.weaponPatternHash ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Raw JSON toggle -->
            <div class="mt-6">
                <button
                    @click="showRawJson = !showRawJson"
                    class="text-sm text-gray-400 hover:text-white transition underline"
                >
                    {{ showRawJson ? 'Masquer' : 'Afficher' }} le JSON brut complet
                </button>
                <pre
                    v-if="showRawJson"
                    class="mt-3 bg-gray-900 border border-gray-800 rounded-xl p-4 text-xs text-gray-300 overflow-x-auto max-h-[600px]"
                >{{ JSON.stringify(rawData, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>
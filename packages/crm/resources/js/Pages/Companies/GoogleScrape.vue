<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import MiniTrendChart from '@crm/Components/MiniTrendChart.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    selectedRangeDays: {
        type: Number,
        required: true,
    },
    availableRangeDays: {
        type: Array,
        required: true,
    },
    tiers: {
        type: Array,
        required: true,
    },
    usageChart: {
        type: Object,
        required: true,
    },
    monthlyUsage: {
        type: Object,
        required: true,
    },
})

const searchTerms = ref('restaurant')
const tier = ref('essentials')
const mode = ref('location')
const location = ref('')
const geolocation = ref('')
const radius = ref(10)
const maxCompanies = ref(20)

const selectedTier = computed(() => props.tiers.find((item) => item.value === tier.value) || props.tiers[0])

const validationMessage = computed(() => {
    if (!searchTerms.value.trim()) {
        return 'Add at least one search term to build the command.'
    }

    if (mode.value === 'location' && !location.value.trim()) {
        return 'Choose a location string to complete the command.'
    }

    if (mode.value === 'geolocation' && !geolocation.value.trim()) {
        return 'Add a latitude and longitude pair to complete the command.'
    }

    return 'Command preview is ready. Execution is still intentionally disabled for now.'
})

const commandPreview = computed(() => {
    const parts = [
        `php artisan leads:google --search="${searchTerms.value || ''}"`,
        `--tier=${tier.value}`,
        `--radius=${radius.value}`,
        `--max-companies=${maxCompanies.value}`,
    ]

    if (mode.value === 'location' && location.value.trim()) {
        parts.push(`--location="${location.value.trim()}"`)
    }

    if (mode.value === 'geolocation' && geolocation.value.trim()) {
        parts.push(`--geolocation="${geolocation.value.trim()}"`)
    }

    return parts.join(' ')
})

const percentUsed = computed(() => Math.min(props.monthlyUsage.percent_used || 0, 100))
</script>

<template>
    <Head title="Google Scrape" />

    <CrmShell
        title="Google Scrape"
        subtitle="Prepare Google Places search inputs and monitor monthly usage by tier."
    >
        <template #actions>
            <Link :href="route('companies.index')" class="williambeeler-button-secondary">
                Back to Companies
            </Link>
        </template>

        <div class="space-y-6">
            <FlashMessages />

            <div class="grid gap-6 xl:grid-cols-[1.4fr,0.9fr]">
                <section class="williambeeler-card px-4 py-5 sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl text-slate-900">Scrape Inputs</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Search terms are required. Use a location string or a geolocation pair, then review the generated command.
                            </p>
                        </div>

                        <span class="rounded-full bg-slate-200 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-600">
                            Execution not wired yet
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Search terms</label>
                            <input v-model="searchTerms" type="text" class="williambeeler-input" placeholder="restaurant, auto repair, med spa" />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">Tier</label>
                            <select v-model="tier" class="williambeeler-select">
                                <option v-for="tierOption in tiers" :key="tierOption.value" :value="tierOption.value">
                                    {{ tierOption.label }} ({{ tierOption.points }} {{ tierOption.points === 1 ? 'point' : 'points' }} per hit)
                                </option>
                            </select>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                    :class="mode === 'location' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'"
                                    @click="mode = 'location'"
                                >
                                    Location
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                    :class="mode === 'geolocation' ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-500'"
                                    @click="mode = 'geolocation'"
                                >
                                    Geolocation
                                </button>
                            </div>

                            <div class="mt-4">
                                <div v-if="mode === 'location'">
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Location</label>
                                    <input v-model="location" type="text" class="williambeeler-input" placeholder="Midwest City, OK" />
                                </div>

                                <div v-else>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Geolocation</label>
                                    <input v-model="geolocation" type="text" class="williambeeler-input" placeholder="35.6528,-97.4781" />
                                    <p class="mt-2 text-sm text-slate-500">
                                        Map pin placement is part of the next pass. For now you can paste coordinates directly.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Radius</label>
                                <input v-model="radius" type="range" min="1" max="25" step="1" class="w-full accent-cyan-500" />
                                <div class="mt-2 flex items-center justify-between text-sm text-slate-500">
                                    <span>1 mile</span>
                                    <span class="font-semibold text-slate-800">{{ radius }} miles</span>
                                    <span>25 miles</span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Max companies</label>
                                <input v-model="maxCompanies" type="number" min="1" step="1" class="williambeeler-input" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="williambeeler-card px-4 py-5 sm:px-6">
                    <h2 class="text-2xl text-slate-900">Command Preview</h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Current tier: {{ selectedTier?.label }}. Radius and max company limits are included automatically.
                    </p>

                    <pre class="mt-5 overflow-x-auto rounded-[1.5rem] bg-slate-950 p-4 text-sm leading-7 text-cyan-200"><code>{{ commandPreview }}</code></pre>

                    <div class="mt-4 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        {{ validationMessage }}
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.25rem] border border-slate-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Points per hit</p>
                            <p class="mt-2 text-3xl text-slate-900">{{ selectedTier?.points || 0 }}</p>
                        </div>

                        <div class="rounded-[1.25rem] border border-slate-200 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Monthly usage</p>
                            <p class="mt-2 text-3xl text-slate-900">{{ monthlyUsage.points_used }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="williambeeler-card px-4 py-5 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-2xl text-slate-900">Usage By Tier</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ usageChart.range_label }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="rangeDays in availableRangeDays"
                            :key="rangeDays"
                            :href="route('companies.googleScrape', { usage_range: rangeDays })"
                            class="rounded-full px-4 py-2 text-sm font-semibold transition"
                            :class="selectedRangeDays === rangeDays
                                ? 'bg-cyan-400 text-slate-950'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                        >
                            {{ rangeDays }} days
                        </Link>
                    </div>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr,0.85fr]">
                    <div class="grid gap-4">
                        <div
                            v-for="dataset in usageChart.datasets"
                            :key="dataset.label"
                            class="rounded-[1.5rem] border border-slate-200 p-4"
                        >
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ dataset.label }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.24em] text-slate-400">
                                        {{ dataset.data.reduce((sum, value) => sum + value, 0) }} total requests
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="usageChart.labels"
                                    :values="dataset.data"
                                    :stroke-color="dataset.border_color"
                                    :fill-color="dataset.background_color"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">
                            Monthly point usage
                        </p>
                        <p class="mt-3 text-3xl text-slate-900">
                            {{ monthlyUsage.points_used.toLocaleString() }} / {{ monthlyUsage.point_limit.toLocaleString() }}
                        </p>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ monthlyUsage.month_label }} resets automatically at the start of the next month.
                        </p>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-cyan-400" :style="{ width: `${percentUsed}%` }" />
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1.25rem] bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Remaining points</p>
                                <p class="mt-2 text-2xl text-slate-900">{{ monthlyUsage.points_remaining.toLocaleString() }}</p>
                            </div>
                            <div class="rounded-[1.25rem] bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Google hits</p>
                                <p class="mt-2 text-2xl text-slate-900">{{ monthlyUsage.request_count.toLocaleString() }}</p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div
                                v-for="tierOption in tiers"
                                :key="tierOption.value"
                                class="flex items-center justify-between rounded-[1.25rem] bg-white px-4 py-3 text-sm"
                            >
                                <span class="font-semibold text-slate-800">{{ tierOption.label }}</span>
                                <span class="text-slate-500">{{ tierOption.points }} {{ tierOption.points === 1 ? 'point' : 'points' }} per hit</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </CrmShell>
</template>

<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import MiniTrendChart from '@crm/Components/MiniTrendChart.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    hasApiKey: Boolean,
    hasUsageApiKey: Boolean,
    keyModeLabel: String,
    identity: Object,
    costs: Object,
    usage: Object,
})

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: false },
    { label: 'Import', href: route('tools.import'), active: false },
    { label: 'Export', href: route('tools.export'), active: false },
    { label: 'Statuses', href: route('statuses.index'), active: false },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: true },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: false },
]

const form = useForm({
    usage_api_key: '',
})
const usageKeyInput = ref(null)

function saveUsageKey() {
    form.post(route('tools.openAiPlatform.usageKey.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}

function removeUsageKey() {
    router.post(route('tools.openAiPlatform.usageKey.remove'))
}
</script>

<template>
    <Head title="OpenAI" />

    <CrmShell
        title="OpenAI"
        subtitle="Review current identity, cost, and usage visibility for the configured OpenAI credentials."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">OpenAI Platform</h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    This page checks the configured OpenAI credentials from the server side and reports what usage and cost data can be read.
                                </p>
                            </div>

                            <div class="flex flex-col items-start gap-3 lg:items-end">
                                <span
                                    class="rounded-full px-4 py-2 text-sm font-semibold"
                                    :class="hasApiKey ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'"
                                >
                                    {{ hasApiKey ? keyModeLabel : 'Missing' }}
                                </span>

                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="williambeeler-button-primary" @click="usageKeyInput?.focus()">
                                        {{ hasUsageApiKey ? 'Update Usage Key' : 'Connect Usage Key' }}
                                    </button>
                                    <button v-if="hasUsageApiKey" type="button" class="williambeeler-button-secondary" @click="removeUsageKey">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="grid gap-4 xl:grid-cols-[0.9fr,1.1fr]">
                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Identity</h2>
                            <div v-if="hasApiKey" class="mt-5 space-y-3 text-sm text-slate-600">
                                <p><span class="font-semibold text-slate-900">Name:</span> {{ identity.name || 'Unavailable' }}</p>
                                <p><span class="font-semibold text-slate-900">Email:</span> {{ identity.email || 'Unavailable' }}</p>
                                <p><span class="font-semibold text-slate-900">Role:</span> {{ identity.role || 'Unavailable' }}</p>
                                <p><span class="font-semibold text-slate-900">Org:</span> {{ identity.organization_title || 'Unavailable' }}</p>
                            </div>
                            <p v-else class="mt-5 text-sm text-slate-500">No OpenAI key is configured for this environment.</p>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Usage Key</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Paste an OpenAI key that can read organization usage and costs. This app stores it as <code>OPENAI_USAGE_API_KEY</code>.
                            </p>

                            <form class="mt-5 space-y-4" @submit.prevent="saveUsageKey">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Usage API Key</label>
                                    <input
                                        ref="usageKeyInput"
                                        v-model="form.usage_api_key"
                                        type="password"
                                        class="williambeeler-input"
                                        placeholder="sk-..."
                                    />
                                    <p v-if="form.errors.usage_api_key" class="mt-2 text-sm text-rose-600">{{ form.errors.usage_api_key }}</p>
                                </div>

                                <button type="submit" class="williambeeler-button-primary" :disabled="form.processing">
                                    Save Key
                                </button>
                            </form>
                        </section>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[1.05fr,0.95fr]">
                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Current Month Cost</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ costs.range_label }}</p>
                            <p class="mt-4 text-3xl text-slate-900">
                                {{ costs.status === 'available' ? `${String(costs.currency || 'usd').toUpperCase()} ${Number(costs.total || 0).toFixed(2)}` : 'Unavailable' }}
                            </p>
                            <p class="mt-2 text-sm text-slate-500">{{ costs.message }}</p>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="costs.daily.map((item) => item.label)"
                                    :values="costs.daily.map((item) => item.value)"
                                    stroke-color="rgba(14, 165, 164, 1)"
                                    fill-color="rgba(14, 165, 164, 0.16)"
                                />
                            </div>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Current Month Usage</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ usage.range_label }}</p>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Requests</p>
                                    <p class="mt-2 text-2xl text-slate-900">{{ Number(usage.requests || 0).toLocaleString() }}</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Input tokens</p>
                                    <p class="mt-2 text-2xl text-slate-900">{{ Number(usage.input_tokens || 0).toLocaleString() }}</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Output tokens</p>
                                    <p class="mt-2 text-2xl text-slate-900">{{ Number(usage.output_tokens || 0).toLocaleString() }}</p>
                                </div>
                            </div>

                            <p class="mt-4 text-sm text-slate-500">{{ usage.message }}</p>
                        </section>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-xl font-semibold text-slate-900">Daily Requests</h2>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="usage.daily_requests.map((item) => item.label)"
                                    :values="usage.daily_requests.map((item) => item.value)"
                                    stroke-color="rgba(0, 168, 150, 1)"
                                    fill-color="rgba(0, 168, 150, 0.16)"
                                />
                            </div>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-xl font-semibold text-slate-900">Daily Total Tokens</h2>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="usage.daily_tokens.map((item) => item.label)"
                                    :values="usage.daily_tokens.map((item) => item.value)"
                                    stroke-color="rgba(244, 63, 94, 1)"
                                    fill-color="rgba(244, 63, 94, 0.16)"
                                />
                            </div>
                        </section>
                    </div>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

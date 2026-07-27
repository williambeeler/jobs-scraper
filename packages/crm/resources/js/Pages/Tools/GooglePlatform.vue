<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import MiniTrendChart from '@crm/Components/MiniTrendChart.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, router } from '@inertiajs/vue3'

const props = defineProps({
    hasPlacesApiKey: Boolean,
    hasCloudCredentials: Boolean,
    hasOAuthClientConfig: Boolean,
    hasBillingAccountId: Boolean,
    connectionLabel: String,
    project: Object,
    billing: Object,
    enabledMapsServices: Object,
    budgets: Object,
    requests: Object,
    errorMetrics: Object,
    latency: Object,
})

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: false },
    { label: 'Import', href: route('tools.import'), active: false },
    { label: 'Export', href: route('tools.export'), active: false },
    { label: 'Statuses', href: route('statuses.index'), active: false },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: false },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: true },
]

function connectGoogle() {
    window.location.href = route('tools.googlePlatform.oauth.redirect')
}

function disconnectGoogle() {
    router.post(route('tools.googlePlatform.remove'))
}
</script>

<template>
    <Head title="Google Platform" />

    <CrmShell
        title="Google Platform"
        subtitle="Review Maps related services, billing, budgets, request volume, and monitoring health."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">Google Maps Platform</h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Connection status for billing, monitoring, and the Maps related services used by this CRM.
                                </p>
                            </div>

                            <div class="flex flex-col items-start gap-3 lg:items-end">
                                <span
                                    class="rounded-full px-4 py-2 text-sm font-semibold"
                                    :class="hasCloudCredentials ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'"
                                >
                                    {{ connectionLabel }}
                                </span>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-if="hasOAuthClientConfig && !hasCloudCredentials"
                                        type="button"
                                        class="williambeeler-button-secondary"
                                        @click="connectGoogle"
                                    >
                                        Connect with Google
                                    </button>
                                    <button
                                        v-if="hasCloudCredentials"
                                        type="button"
                                        class="williambeeler-button-secondary"
                                        @click="disconnectGoogle"
                                    >
                                        Disconnect
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div
                        v-if="!hasOAuthClientConfig"
                        class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                    >
                        This page is ready for a one click Google sign in flow, but the CRM still needs its Google OAuth app configured in the environment.
                    </div>

                    <div class="grid gap-4 xl:grid-cols-3">
                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Project</p>
                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <p><span class="font-semibold text-slate-900">Project ID:</span> {{ project.id || 'Not connected' }}</p>
                                <p><span class="font-semibold text-slate-900">Billing ID:</span> {{ hasBillingAccountId ? 'Configured' : 'Not configured' }}</p>
                                <p><span class="font-semibold text-slate-900">Places key:</span> {{ hasPlacesApiKey ? 'Configured' : 'Missing' }}</p>
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Billing</p>
                            <p class="mt-4 text-2xl text-slate-900">
                                {{ billing.status === 'available' ? (billing.billing_enabled ? 'Enabled' : 'Disabled') : 'Unavailable' }}
                            </p>
                            <p class="mt-2 text-sm text-slate-500">{{ billing.message }}</p>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Maps Services</p>
                            <p class="mt-4 text-2xl text-slate-900">
                                {{ enabledMapsServices.status === 'available' ? enabledMapsServices.items.length : 0 }}
                            </p>
                            <p class="mt-2 text-sm text-slate-500">{{ enabledMapsServices.message }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-3">
                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-sm font-semibold text-slate-900">Daily Requests</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.24em] text-slate-400">{{ requests.range_label }}</p>
                            <p class="mt-4 text-3xl text-slate-900">{{ Number(requests.total || 0).toLocaleString() }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ requests.message }}</p>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="requests.daily.map((item) => item.label)"
                                    :values="requests.daily.map((item) => item.value)"
                                    stroke-color="rgba(66, 133, 244, 1)"
                                    fill-color="rgba(66, 133, 244, 0.16)"
                                />
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-sm font-semibold text-slate-900">Daily Errors</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.24em] text-slate-400">{{ errorMetrics.range_label }}</p>
                            <p class="mt-4 text-3xl text-slate-900">{{ Number(errorMetrics.total || 0).toLocaleString() }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ errorMetrics.message }}</p>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="errorMetrics.daily.map((item) => item.label)"
                                    :values="errorMetrics.daily.map((item) => item.value)"
                                    stroke-color="rgba(234, 67, 53, 1)"
                                    fill-color="rgba(234, 67, 53, 0.16)"
                                />
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] border border-slate-200 p-5">
                            <p class="text-sm font-semibold text-slate-900">Median Latency</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.24em] text-slate-400">{{ latency.range_label }}</p>
                            <p class="mt-4 text-3xl text-slate-900">{{ latency.median_ms ? `${Number(latency.median_ms).toFixed(2)} ms` : 'No data' }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ latency.message }}</p>
                            <div class="mt-4">
                                <MiniTrendChart
                                    :labels="latency.daily.map((item) => item.label)"
                                    :values="latency.daily.map((item) => item.value)"
                                    stroke-color="rgba(52, 168, 83, 1)"
                                    fill-color="rgba(52, 168, 83, 0.16)"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Enabled Maps Related Services</h2>
                            <div v-if="enabledMapsServices.items?.length" class="mt-5 space-y-3">
                                <div
                                    v-for="service in enabledMapsServices.items"
                                    :key="service.name"
                                    class="rounded-[1.25rem] bg-slate-50 px-4 py-3"
                                >
                                    <p class="font-semibold text-slate-900">{{ service.title }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ service.name }}</p>
                                </div>
                            </div>
                            <p v-else class="mt-5 text-sm text-slate-500">{{ enabledMapsServices.message }}</p>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 p-5">
                            <h2 class="text-2xl text-slate-900">Budgets</h2>
                            <div v-if="budgets.items?.length" class="mt-5 space-y-3">
                                <div
                                    v-for="budget in budgets.items"
                                    :key="budget.display_name"
                                    class="rounded-[1.25rem] bg-slate-50 px-4 py-3"
                                >
                                    <p class="font-semibold text-slate-900">{{ budget.display_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ budget.amount }}</p>
                                    <p class="mt-1 text-xs uppercase tracking-[0.22em] text-slate-400">{{ budget.thresholds }}</p>
                                </div>
                            </div>
                            <p v-else class="mt-5 text-sm text-slate-500">{{ budgets.message }}</p>
                        </section>
                    </div>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

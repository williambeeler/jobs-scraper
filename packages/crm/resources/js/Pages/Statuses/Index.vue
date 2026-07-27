<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import PaginationLinks from '@crm/Components/PaginationLinks.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    relationships: {
        type: Object,
        required: true,
    },
})

const searchTerm = ref(props.filters.search_term || '')

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: false },
    { label: 'Import', href: route('tools.import'), active: false },
    { label: 'Export', href: route('tools.export'), active: false },
    { label: 'Statuses', href: route('statuses.index'), active: true },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: false },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: false },
]

function search() {
    router.get(route('statuses.index'), {
        search_term: searchTerm.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Statuses" />

    <CrmShell
        title="Statuses"
        subtitle="Review the lifecycle labels and relationship definitions used across company records."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-2xl text-slate-900">System Statuses</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ statuses.meta.total }} statuses and {{ relationships.meta.total }} relationships available.
                            </p>
                        </div>

                        <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="search">
                            <input v-model="searchTerm" type="search" class="williambeeler-input min-w-64" placeholder="Search statuses" />
                            <button type="submit" class="williambeeler-button-secondary">Search</button>
                            <Link v-if="filters.search_term" :href="route('statuses.index')" class="williambeeler-button-secondary">Reset</Link>
                        </form>
                    </div>

                    <div class="grid gap-6 xl:grid-cols-2">
                        <section class="rounded-[1.5rem] border border-slate-200">
                            <div class="border-b border-slate-200 px-4 py-4">
                                <h3 class="text-lg font-semibold text-slate-900">Statuses</h3>
                            </div>

                            <div class="overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-100/90 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                                        <tr>
                                            <th class="px-4 py-4">Name</th>
                                            <th class="px-4 py-4">Slug</th>
                                            <th class="px-4 py-4">Color</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                                        <tr v-for="status in statuses.data" :key="status.id">
                                            <td class="px-4 py-4">
                                                <Link :href="status.show_url" class="font-semibold text-slate-900 hover:text-cyan-600">
                                                    {{ status.name }}
                                                </Link>
                                                <p class="mt-1 text-xs text-slate-500">{{ status.description || 'No description' }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-500">{{ status.slug }}</td>
                                            <td class="px-4 py-4">
                                                <span
                                                    v-if="status.color"
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white"
                                                    :style="{ backgroundColor: status.color }"
                                                >
                                                    {{ status.color }}
                                                </span>
                                                <span v-else class="text-slate-400">Not set</span>
                                            </td>
                                        </tr>

                                        <tr v-if="statuses.data.length === 0">
                                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                                                No statuses found for this search.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="px-4 py-4">
                                <PaginationLinks :links="statuses.links" />
                            </div>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200">
                            <div class="border-b border-slate-200 px-4 py-4">
                                <h3 class="text-lg font-semibold text-slate-900">Relationships</h3>
                            </div>

                            <div class="overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-100/90 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                                        <tr>
                                            <th class="px-4 py-4">Name</th>
                                            <th class="px-4 py-4">Slug</th>
                                            <th class="px-4 py-4">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 bg-white text-sm">
                                        <tr v-for="relationship in relationships.data" :key="relationship.id">
                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-slate-900">{{ relationship.name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ relationship.description || 'No description' }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-500">{{ relationship.slug }}</td>
                                            <td class="px-4 py-4 text-slate-500">{{ relationship.created_at_label }}</td>
                                        </tr>

                                        <tr v-if="relationships.data.length === 0">
                                            <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                                                No relationships found for this search.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="px-4 py-4">
                                <PaginationLinks :links="relationships.links" />
                            </div>
                        </section>
                    </div>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

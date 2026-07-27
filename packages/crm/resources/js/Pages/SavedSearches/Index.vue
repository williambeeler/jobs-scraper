<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import PaginationLinks from '@crm/Components/PaginationLinks.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    savedSearches: {
        type: Object,
        required: true,
    },
})

function formatFilters(parameters) {
    const entries = Object.entries(parameters || {})

    if (entries.length === 0) {
        return 'No filters saved.'
    }

    return entries
        .map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`)
        .join(' | ')
}
</script>

<template>
    <Head title="Saved Searches" />

    <CrmShell
        title="Saved Searches"
        subtitle="Quickly reopen the company views you use most often."
    >
        <div class="space-y-6">
            <FlashMessages />

            <section class="williambeeler-card px-4 py-5 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">
                            Companies Workspace
                        </p>
                        <h2 class="mt-3 text-2xl text-slate-900">
                            Company Searches
                        </h2>
                        <p class="mt-2 text-sm text-slate-500">
                            {{ savedSearches.meta.total }} saved search{{ savedSearches.meta.total === 1 ? '' : 'es' }} ready to reopen.
                        </p>
                    </div>

                    <Link :href="route('companies.index')" class="williambeeler-button-secondary">
                        Open Companies
                    </Link>
                </div>

                <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-100/90">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                                <th class="px-4 py-4">Name</th>
                                <th class="px-4 py-4">Filters</th>
                                <th class="px-4 py-4">Saved</th>
                                <th class="px-4 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
                            <tr v-for="savedSearch in savedSearches.data" :key="savedSearch.id">
                                <td class="px-4 py-4 font-semibold text-slate-900">
                                    {{ savedSearch.name }}
                                </td>
                                <td class="px-4 py-4 text-slate-500">
                                    {{ formatFilters(savedSearch.parameters) }}
                                </td>
                                <td class="px-4 py-4 text-slate-500">
                                    {{ savedSearch.created_at_label }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Link :href="savedSearch.open_url" class="williambeeler-button-secondary">
                                        Open
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="savedSearches.data.length === 0">
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No saved company searches yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    <PaginationLinks :links="savedSearches.links" />
                </div>
            </section>
        </div>
    </CrmShell>
</template>

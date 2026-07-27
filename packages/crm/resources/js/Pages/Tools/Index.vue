<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, router } from '@inertiajs/vue3'

defineProps({
    backups: {
        type: Array,
        required: true,
    },
    isSpatieBackupInstalled: {
        type: Boolean,
        required: true,
    },
})

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: true },
    { label: 'Import', href: route('tools.import'), active: false },
    { label: 'Export', href: route('tools.export'), active: false },
    { label: 'Statuses', href: route('statuses.index'), active: false },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: false },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: false },
]

function runBackup() {
    router.post(route('tools.backups.run'))
}
</script>

<template>
    <Head title="Tools" />

    <CrmShell
        title="Tools"
        subtitle="Operations utilities for backups, maintenance, imports, exports, and connected services."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-2xl text-slate-900">Database Backup</h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Run a database only backup using Spatie Laravel Backup, then download any recent archive below.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="williambeeler-button-primary"
                            :disabled="!isSpatieBackupInstalled"
                            @click="runBackup"
                        >
                            Backup
                        </button>
                    </div>

                    <div
                        v-if="!isSpatieBackupInstalled"
                        class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                    >
                        Spatie Laravel Backup is not installed in this environment yet.
                    </div>

                    <div class="overflow-hidden rounded-[1.5rem] border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-100/90 text-left text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-4">File</th>
                                    <th class="px-4 py-4">Disk</th>
                                    <th class="px-4 py-4">Size</th>
                                    <th class="px-4 py-4">Created</th>
                                    <th class="px-4 py-4 text-right">Download</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
                                <tr v-for="backup in backups" :key="`${backup.disk}:${backup.path}`">
                                    <td class="px-4 py-4 font-medium text-slate-900">{{ backup.path }}</td>
                                    <td class="px-4 py-4">{{ backup.disk }}</td>
                                    <td class="px-4 py-4">{{ backup.size_label }}</td>
                                    <td class="px-4 py-4 text-slate-500">{{ backup.last_modified_label }}</td>
                                    <td class="px-4 py-4 text-right">
                                        <a :href="backup.download_url" class="williambeeler-button-secondary">
                                            Download
                                        </a>
                                    </td>
                                </tr>

                                <tr v-if="backups.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                        No backup files found yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

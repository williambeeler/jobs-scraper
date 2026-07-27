<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    companyCount: {
        type: Number,
        required: true,
    },
    exportOptions: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const selectedExportType = ref(props.exportOptions[0]?.value || '')
const exportState = ref({
    isRunning: false,
    status: 'Choose an export type, then start the export.',
    processed: 0,
    total: props.companyCount,
    alert: '',
    alertType: '',
})

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: false },
    { label: 'Import', href: route('tools.import'), active: false },
    { label: 'Export', href: route('tools.export'), active: true },
    { label: 'Statuses', href: route('statuses.index'), active: false },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: false },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: false },
]

const selectedOption = computed(() => props.exportOptions.find((option) => option.value === selectedExportType.value) || props.exportOptions[0])

const progressPercent = computed(() => {
    if (!exportState.value.total) {
        return 0
    }

    return Math.round((exportState.value.processed / exportState.value.total) * 100)
})

async function startExport() {
    if (!selectedExportType.value || exportState.value.isRunning) {
        return
    }

    exportState.value = {
        isRunning: true,
        status: 'Preparing export...',
        processed: 0,
        total: Number(selectedOption.value?.total || props.companyCount),
        alert: '',
        alertType: '',
    }

    try {
        const response = await fetch(route('tools.exports.start'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': page.props.csrfToken,
            },
            body: JSON.stringify({
                export_type: selectedExportType.value,
            }),
        })

        const payload = await response.json()

        if (!response.ok || !payload.ok) {
            throw new Error('The export could not be started.')
        }

        await pollExport(payload.export_id)
    } catch (error) {
        exportState.value.isRunning = false
        exportState.value.alert = error instanceof Error ? error.message : 'The export could not be started.'
        exportState.value.alertType = 'error'
    }
}

async function pollExport(exportId) {
    const response = await fetch(route('tools.exports.status', { exportId }), {
        headers: {
            Accept: 'application/json',
        },
    })

    const payload = await response.json()

    if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'Unable to read export progress.')
    }

    exportState.value.processed = Number(payload.processed || 0)
    exportState.value.total = Number(payload.total || exportState.value.total)

    if (payload.status === 'pending') {
        exportState.value.status = 'Preparing export...'
        window.setTimeout(() => pollExport(exportId), 1000)
        return
    }

    if (payload.status === 'running') {
        exportState.value.status = `Exported ${payload.processed} of ${payload.total} businesses...`
        window.setTimeout(() => pollExport(exportId), 1000)
        return
    }

    exportState.value.isRunning = false

    if (payload.status === 'completed' && payload.download_url) {
        exportState.value.status = 'Export complete. Download starting...'
        exportState.value.processed = exportState.value.total
        exportState.value.alert = 'Export complete. Your file should begin downloading immediately.'
        exportState.value.alertType = 'success'
        window.location.href = payload.download_url
        return
    }

    exportState.value.alert = payload.error || 'The export could not be completed.'
    exportState.value.alertType = 'error'
}
</script>

<template>
    <Head title="Export" />

    <CrmShell
        title="Export"
        subtitle="Generate full company exports, monitor progress, and download the finished CSV."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">Business Exports</h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Export every row from the companies table as a CSV with database column headers.
                                </p>
                            </div>

                            <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Records ready: <span class="font-semibold text-slate-900">{{ companyCount.toLocaleString() }}</span>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-[1fr,auto] md:items-end">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Export type</label>
                                <select v-model="selectedExportType" class="williambeeler-select">
                                    <option v-for="option in exportOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <button type="button" class="williambeeler-button-primary" :disabled="exportState.isRunning" @click="startExport">
                                {{ exportState.isRunning ? 'Exporting...' : 'Export' }}
                            </button>
                        </div>
                    </section>

                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <h2 class="text-2xl text-slate-900">Export Progress</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ exportState.status }}</p>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-cyan-400" :style="{ width: `${progressPercent}%` }" />
                        </div>

                        <p class="mt-3 text-sm text-slate-500">
                            {{ exportState.processed }} / {{ exportState.total }} complete
                        </p>

                        <div
                            v-if="exportState.alert"
                            class="mt-4 rounded-2xl px-4 py-3 text-sm font-medium"
                            :class="exportState.alertType === 'success'
                                ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border border-rose-200 bg-rose-50 text-rose-700'"
                        >
                            {{ exportState.alert }}
                        </div>
                    </section>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

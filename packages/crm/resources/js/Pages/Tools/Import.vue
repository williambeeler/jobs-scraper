<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import SectionTabs from '@crm/Components/SectionTabs.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    campaigns: {
        type: Array,
        required: true,
    },
    tags: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    relationships: {
        type: Array,
        required: true,
    },
    defaultStatusId: {
        type: Number,
        required: true,
    },
    leadRelationshipId: {
        type: Number,
        required: true,
    },
    templateDownloadUrl: {
        type: String,
        required: true,
    },
    maxUploadSize: {
        type: String,
        required: true,
    },
})

const page = usePage()
const fileInput = ref(null)
const campaignsValue = ref('')
const tagsValue = ref('')
const source = ref('')
const statusId = ref(props.defaultStatusId)
const relationshipId = ref(props.leadRelationshipId)
const progress = ref({
    visible: false,
    status: 'Waiting to start...',
    processed: 0,
    total: 0,
    imported: 0,
    skipped: 0,
    errors: [],
    alert: '',
    alertType: '',
})
const isUploading = ref(false)

const tabItems = [
    { label: 'Backups', href: route('tools.index'), active: false },
    { label: 'Import', href: route('tools.import'), active: true },
    { label: 'Export', href: route('tools.export'), active: false },
    { label: 'Statuses', href: route('statuses.index'), active: false },
    { label: 'OpenAI', href: route('tools.openAiPlatform'), active: false },
    { label: 'Google Platform', href: route('tools.googlePlatform'), active: false },
]

const percent = computed(() => {
    if (!progress.value.total) {
        return 0
    }

    return Math.round((progress.value.processed / progress.value.total) * 100)
})

async function submitImport() {
    const file = fileInput.value?.files?.[0]

    if (!file || isUploading.value) {
        return
    }

    isUploading.value = true
    progress.value = {
        visible: true,
        status: 'Uploading file...',
        processed: 0,
        total: 0,
        imported: 0,
        skipped: 0,
        errors: [],
        alert: '',
        alertType: '',
    }

    const formData = new FormData()
    formData.append('file', file)
    formData.append('campaigns', campaignsValue.value)
    formData.append('tags', tagsValue.value)
    formData.append('source', source.value)
    formData.append('status_id', String(statusId.value))
    formData.append('relationship_id', String(relationshipId.value))

    try {
        const response = await fetch(route('tools.import.companies'), {
            method: 'POST',
            headers: {
                Accept: 'application/x-ndjson',
                'X-CSRF-TOKEN': page.props.csrfToken,
            },
            body: formData,
        })

        if (!response.ok || !response.body) {
            throw new Error('The import could not be started.')
        }

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let buffer = ''

        while (true) {
            const { done, value } = await reader.read()
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done })

            const lines = buffer.split('\n')
            buffer = lines.pop() || ''

            for (const line of lines) {
                if (!line.trim()) {
                    continue
                }

                const payload = JSON.parse(line)

                progress.value.status = payload.message || progress.value.status
                progress.value.processed = Number(payload.processed || 0)
                progress.value.total = Number(payload.total || 0)
                progress.value.imported = Number(payload.imported || 0)
                progress.value.skipped = Number(payload.skipped || 0)

                if (payload.type === 'complete') {
                    progress.value.errors = payload.errors || []
                    progress.value.alert = payload.message || 'Import complete.'
                    progress.value.alertType = 'success'
                }

                if (payload.type === 'error') {
                    progress.value.alert = payload.message || 'The import failed.'
                    progress.value.alertType = 'error'
                }
            }

            if (done) {
                break
            }
        }
    } catch (error) {
        progress.value.alert = error instanceof Error ? error.message : 'The import failed.'
        progress.value.alertType = 'error'
    } finally {
        isUploading.value = false
    }
}
</script>

<template>
    <Head title="Import" />

    <CrmShell
        title="Import"
        subtitle="Upload company CSV files, assign fallback metadata, and monitor progress in real time."
    >
        <div class="space-y-6">
            <FlashMessages />

            <SectionTabs :items="tabItems">
                <div class="space-y-6">
                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">Company Import</h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Download the template, fill in your company rows, and upload the finished CSV here.
                                </p>
                                <a :href="templateDownloadUrl" class="mt-3 inline-flex text-sm font-semibold text-cyan-600 hover:text-cyan-700">
                                    Download the company import template
                                </a>
                            </div>

                            <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Maximum upload size: <span class="font-semibold text-slate-900">{{ maxUploadSize }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[1.5rem] border border-slate-200 p-5">
                        <form class="grid gap-4" @submit.prevent="submitImport">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">CSV file</label>
                                <input ref="fileInput" type="file" accept=".csv,text/csv" class="williambeeler-input" required />
                                <p class="mt-2 text-sm text-slate-500">
                                    Ignored columns are <code>id</code>, <code>uuid</code>, <code>opportunity_score</code>, <code>opportunity_summary</code>, and <code>meta</code>.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Campaigns</label>
                                    <input v-model="campaignsValue" type="text" class="williambeeler-input" placeholder="Spring Launch, Q2 Outreach" />
                                    <p class="mt-2 text-sm text-slate-500">
                                        Existing campaigns: {{ campaigns.map((campaign) => campaign.name).join(', ') || 'None yet' }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Tags</label>
                                    <input v-model="tagsValue" type="text" class="williambeeler-input" placeholder="HVAC, urgent, lead" />
                                    <p class="mt-2 text-sm text-slate-500">
                                        Existing tags: {{ tags.map((tag) => tag.name).join(', ') || 'None yet' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Source</label>
                                <input v-model="source" type="text" class="williambeeler-input" placeholder="Optional override for every imported company" />
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                                    <select v-model="statusId" class="williambeeler-select">
                                        <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Relationship</label>
                                    <select v-model="relationshipId" class="williambeeler-select">
                                        <option v-for="relationship in relationships" :key="relationship.id" :value="relationship.id">{{ relationship.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="williambeeler-button-primary" :disabled="isUploading">
                                    {{ isUploading ? 'Importing...' : 'Upload and Import' }}
                                </button>
                            </div>
                        </form>
                    </section>

                    <section v-if="progress.visible" class="rounded-[1.5rem] border border-slate-200 p-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">Import Progress</h2>
                                <p class="mt-2 text-sm text-slate-500">{{ progress.status }}</p>
                            </div>
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-slate-400">Imported</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ progress.imported }}</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-slate-400">Skipped</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ progress.skipped }}</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-slate-400">Total</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ progress.total }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full rounded-full bg-cyan-400" :style="{ width: `${percent}%` }" />
                        </div>
                        <p class="mt-3 text-sm text-slate-500">{{ progress.processed }} / {{ progress.total }} processed</p>

                        <div
                            v-if="progress.alert"
                            class="mt-4 rounded-2xl px-4 py-3 text-sm font-medium"
                            :class="progress.alertType === 'success'
                                ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                                : 'border border-rose-200 bg-rose-50 text-rose-700'"
                        >
                            {{ progress.alert }}
                        </div>

                        <ul v-if="progress.errors.length > 0" class="mt-4 list-disc space-y-2 pl-5 text-sm text-rose-600">
                            <li v-for="error in progress.errors" :key="error">{{ error }}</li>
                        </ul>
                    </section>
                </div>
            </SectionTabs>
        </div>
    </CrmShell>
</template>

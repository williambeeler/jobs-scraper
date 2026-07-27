<script setup>
import RichTextEditor from '@crm/Components/RichTextEditor.vue'
import { computed, ref } from 'vue'

const props = defineProps({
    companyData: {
        type: Object,
        required: true,
    },
    statusOptions: {
        type: Array,
        required: true,
    },
    relationshipOptions: {
        type: Array,
        required: true,
    },
})

const company = ref(JSON.parse(JSON.stringify(props.companyData)))
const isEditingCompanyInformation = ref(false)
const isEditingPrimaryContact = ref(false)
const isEditingAddress = ref(false)
const savingCompanyInformation = ref(false)
const savingPrimaryContact = ref(false)
const savingAddress = ref(false)
const callbackForm = ref({
    needs_callback: company.value.needs_callback,
    callback_at: company.value.callback_at || '',
})
const classificationForm = ref({
    status_id: company.value.status_id || '',
    relationship_id: company.value.relationship_id || '',
})
const noteForm = ref({
    content: '',
})
const phoneCallForm = ref({
    called_at: new Date().toISOString().slice(0, 16),
    notes: '',
})
const savingCallback = ref(false)
const savingClassification = ref(false)
const savingNote = ref(false)
const savingPhoneCall = ref(false)
const companyInformationForm = ref(createCompanyInformationForm())
const primaryContactForm = ref(createPrimaryContactForm())
const addressForm = ref(createAddressForm())

const addressLabel = computed(() => {
    if (! company.value.address_line1) {
        return 'No address on file.'
    }

    return `${company.value.address_line1}${company.value.address_line2 ? `\n${company.value.address_line2}` : ''}\n${company.value.city || ''}${company.value.city && company.value.state ? ', ' : ''}${company.value.state || ''} ${company.value.postal_code || ''}\n${company.value.country || ''}`.trim()
})

function createCompanyInformationForm() {
    return {
        name: company.value.name || '',
        alias: company.value.alias || '',
        website: company.value.website || '',
        email: company.value.email || '',
        phone: company.value.phone || '',
        source: company.value.source || '',
        opportunity_score_index: company.value.opportunity_score_index ?? '',
        opportunity_summary: company.value.opportunity_summary || '',
    }
}

function createPrimaryContactForm() {
    return {
        poc_name: company.value.poc_name || '',
        poc_email: company.value.poc_email || '',
        poc_phone: company.value.poc_phone || '',
    }
}

function createAddressForm() {
    return {
        address_line1: company.value.address_line1 || '',
        address_line2: company.value.address_line2 || '',
        city: company.value.city || '',
        state: company.value.state || '',
        postal_code: company.value.postal_code || '',
        country: company.value.country || '',
    }
}

function baseUpdatePayload() {
    return {
        name: company.value.name || '',
        alias: company.value.alias || '',
        website: company.value.website || '',
        email: company.value.email || '',
        phone: company.value.phone || '',
        needs_callback: company.value.needs_callback,
        callback_at: company.value.callback_at || '',
        poc_name: company.value.poc_name || '',
        poc_email: company.value.poc_email || '',
        poc_phone: company.value.poc_phone || '',
        address_line1: company.value.address_line1 || '',
        address_line2: company.value.address_line2 || '',
        city: company.value.city || '',
        state: company.value.state || '',
        postal_code: company.value.postal_code || '',
        country: company.value.country || '',
        source: company.value.source || '',
        status_id: company.value.status_id || '',
        relationship_id: company.value.relationship_id || '',
        opportunity_score_index: company.value.opportunity_score_index ?? '',
        opportunity_summary: company.value.opportunity_summary || '',
        meta: JSON.stringify(company.value.meta || {}),
        tags: company.value.tags || [],
        campaigns: company.value.campaigns || [],
    }
}

async function saveSection(changes) {
    const response = await window.axios.patch(company.value.update_url, {
        ...baseUpdatePayload(),
        ...changes,
    }, {
        headers: {
            Accept: 'application/json',
        },
    })

    company.value = response.data.company
    callbackForm.value = {
        needs_callback: company.value.needs_callback,
        callback_at: company.value.callback_at || '',
    }
    classificationForm.value = {
        status_id: company.value.status_id || '',
        relationship_id: company.value.relationship_id || '',
    }
}

async function saveCompanyInformation() {
    savingCompanyInformation.value = true

    try {
        const changes = {
            name: companyInformationForm.value.name,
            alias: companyInformationForm.value.alias,
            website: companyInformationForm.value.website,
            email: companyInformationForm.value.email,
            phone: companyInformationForm.value.phone,
            source: companyInformationForm.value.source,
            opportunity_score_index: companyInformationForm.value.opportunity_score_index === ''
                ? null
                : Number(companyInformationForm.value.opportunity_score_index),
            opportunity_summary: companyInformationForm.value.opportunity_summary,
        }

        await saveSection(changes)
        isEditingCompanyInformation.value = false
        companyInformationForm.value = createCompanyInformationForm()
    } finally {
        savingCompanyInformation.value = false
    }
}

function cancelCompanyInformationEdit() {
    companyInformationForm.value = createCompanyInformationForm()
    isEditingCompanyInformation.value = false
}

async function savePrimaryContact() {
    savingPrimaryContact.value = true

    try {
        const changes = {
            poc_name: primaryContactForm.value.poc_name,
            poc_email: primaryContactForm.value.poc_email,
            poc_phone: primaryContactForm.value.poc_phone,
        }

        await saveSection(changes)
        isEditingPrimaryContact.value = false
        primaryContactForm.value = createPrimaryContactForm()
    } finally {
        savingPrimaryContact.value = false
    }
}

function cancelPrimaryContactEdit() {
    primaryContactForm.value = createPrimaryContactForm()
    isEditingPrimaryContact.value = false
}

async function saveAddress() {
    savingAddress.value = true

    try {
        const changes = {
            address_line1: addressForm.value.address_line1,
            address_line2: addressForm.value.address_line2,
            city: addressForm.value.city,
            state: addressForm.value.state,
            postal_code: addressForm.value.postal_code,
            country: addressForm.value.country,
        }

        await saveSection(changes)
        isEditingAddress.value = false
        addressForm.value = createAddressForm()
    } finally {
        savingAddress.value = false
    }
}

function cancelAddressEdit() {
    addressForm.value = createAddressForm()
    isEditingAddress.value = false
}

async function saveCallbackPreference() {
    savingCallback.value = true

    try {
        const response = await window.axios.patch(company.value.callback_update_url, {
            needs_callback: callbackForm.value.needs_callback,
            callback_at: callbackForm.value.callback_at,
        }, {
            headers: {
                Accept: 'application/json',
            },
        })

        company.value.needs_callback = response.data.needsCallback
        company.value.callback_at = response.data.callbackAt || ''
        company.value.callback_at_label = response.data.callbackAtIso
            ? new Intl.DateTimeFormat('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            }).format(new Date(response.data.callbackAtIso))
            : null
    } finally {
        savingCallback.value = false
    }
}

async function saveClassification() {
    savingClassification.value = true

    try {
        await window.axios.patch(company.value.classification_update_url, {
            status_id: classificationForm.value.status_id,
            relationship_id: classificationForm.value.relationship_id,
        }, {
            headers: {
                Accept: 'application/json',
            },
        })

        company.value.status_id = classificationForm.value.status_id
        company.value.relationship_id = classificationForm.value.relationship_id
        company.value.status = props.statusOptions.find((status) => status.id === classificationForm.value.status_id) || null
        company.value.relationship = props.relationshipOptions.find((relationship) => relationship.id === classificationForm.value.relationship_id) || null
    } finally {
        savingClassification.value = false
    }
}

async function submitNote() {
    savingNote.value = true

    try {
        const response = await window.axios.post(company.value.notes_store_url, {
            content: noteForm.value.content,
        }, {
            headers: {
                Accept: 'application/json',
            },
        })

        company.value.notes.unshift(response.data.note)
        noteForm.value.content = ''
    } finally {
        savingNote.value = false
    }
}

async function submitPhoneCall() {
    savingPhoneCall.value = true

    try {
        const response = await window.axios.post(company.value.phone_calls_store_url, {
            called_at: phoneCallForm.value.called_at,
            notes: phoneCallForm.value.notes,
        }, {
            headers: {
                Accept: 'application/json',
            },
        })

        company.value.phone_calls.unshift(response.data.phoneCall)
        phoneCallForm.value.notes = ''
        phoneCallForm.value.called_at = new Date().toISOString().slice(0, 16)
    } finally {
        savingPhoneCall.value = false
    }
}

function formatMeta(meta) {
    if (! meta || Object.keys(meta).length === 0) {
        return ''
    }

    return JSON.stringify(meta, null, 2)
}
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,0.95fr)]">
        <div class="space-y-6">
            <section class="williambeeler-card p-6 md:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Company Information</h2>

                    <div class="flex items-center gap-2">
                        <template v-if="isEditingCompanyInformation">
                            <button type="button" class="williambeeler-button-secondary" :disabled="savingCompanyInformation" @click="cancelCompanyInformationEdit">
                                Cancel
                            </button>
                            <button type="button" class="williambeeler-button-primary" :disabled="savingCompanyInformation" @click="saveCompanyInformation">
                                {{ savingCompanyInformation ? 'Saving...' : 'Save' }}
                            </button>
                        </template>
                        <button v-else type="button" class="williambeeler-button-secondary" @click="isEditingCompanyInformation = true">
                            Edit
                        </button>
                    </div>
                </div>

                <div v-if="isEditingCompanyInformation" class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Company Name</label>
                        <input v-model="companyInformationForm.name" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Alias</label>
                        <input v-model="companyInformationForm.alias" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Website</label>
                        <input v-model="companyInformationForm.website" type="url" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                        <input v-model="companyInformationForm.email" type="email" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Phone</label>
                        <input v-model="companyInformationForm.phone" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Source</label>
                        <input v-model="companyInformationForm.source" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Opportunity Score</label>
                        <input v-model="companyInformationForm.opportunity_score_index" type="number" min="0" max="100" class="williambeeler-input mt-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Opportunity Summary</label>
                        <textarea v-model="companyInformationForm.opportunity_summary" rows="4" class="williambeeler-input mt-2" />
                    </div>
                </div>

                <template v-else>
                    <dl class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Alias</dt>
                            <dd class="mt-2 text-base text-slate-800">{{ company.alias || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Website</dt>
                            <dd class="mt-2 text-base text-slate-800">
                                <a v-if="company.website" :href="company.website" target="_blank" rel="noreferrer" class="text-cyan-700 underline underline-offset-4">
                                    {{ company.website }}
                                </a>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                            <dd class="mt-2 text-base text-slate-800">{{ company.email || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</dt>
                            <dd class="mt-2">
                                <a
                                    v-if="company.google_voice_url"
                                    :href="company.google_voice_url"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="inline-flex rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-700"
                                >
                                    {{ company.phone }}
                                </a>
                                <span v-else class="text-base text-slate-800">—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Source</dt>
                            <dd class="mt-2 text-base text-slate-800">{{ company.source || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Opportunity Score</dt>
                            <dd class="mt-2 text-base text-slate-800">{{ company.opportunity_score_index ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tags</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="tag in company.tags" :key="tag" class="rounded-[5px] bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ tag }}</span>
                                <span v-if="company.tags.length === 0" class="text-sm text-slate-500">No tags</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Campaigns</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span v-for="campaign in company.campaigns" :key="campaign" class="rounded-[5px] bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">{{ campaign }}</span>
                                <span v-if="company.campaigns.length === 0" class="text-sm text-slate-500">No campaigns</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Opportunity Summary</p>
                        <p class="mt-3 whitespace-pre-wrap text-base text-slate-700">{{ company.opportunity_summary || 'No summary yet.' }}</p>
                    </div>
                </template>
            </section>

            <section class="williambeeler-card p-6 md:p-8">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Notes</h2>
                </div>

                <form class="mt-6" @submit.prevent="submitNote">
                    <RichTextEditor input-id="company-note-editor" v-model="noteForm.content" placeholder="Capture meeting context, objections, or next steps" />
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="williambeeler-button-primary" :disabled="savingNote">
                            {{ savingNote ? 'Saving...' : 'Save Note' }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 space-y-4">
                    <article v-for="note in company.notes" :key="note.id" class="rounded-2xl border border-slate-200 px-4 py-4">
                        <div class="williambeeler-rich-text" v-html="note.content"></div>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ note.created_at_label }}</p>
                    </article>
                    <p v-if="company.notes.length === 0" class="text-sm text-slate-500">No notes yet for this company.</p>
                </div>
            </section>

            <section class="williambeeler-card p-6 md:p-8">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Additional Data</h2>
                </div>
                <pre v-if="formatMeta(company.meta)" class="mt-6 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-sm text-slate-100">{{ formatMeta(company.meta) }}</pre>
                <p v-else class="mt-6 text-sm text-slate-500">No meta data.</p>
            </section>
        </div>

        <div class="space-y-6">
            <section class="williambeeler-card p-6 md:p-8">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Callback and Classification</h2>
                </div>

                <div class="mt-6 space-y-6">
                    <div>
                        <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            <input v-model="callbackForm.needs_callback" type="checkbox" class="rounded border-slate-300 text-cyan-500 focus:ring-cyan-400">
                            Call back
                        </label>
                        <div v-if="callbackForm.needs_callback" class="mt-4">
                            <label class="block text-sm font-semibold text-slate-700">Call Back Date</label>
                            <input v-model="callbackForm.callback_at" type="datetime-local" class="williambeeler-input mt-2">
                        </div>
                        <div class="mt-4 flex justify-end">
                            <button type="button" class="williambeeler-button-secondary" :disabled="savingCallback" @click="saveCallbackPreference">
                                {{ savingCallback ? 'Saving...' : 'Save Callback' }}
                            </button>
                        </div>
                        <p class="mt-3 text-sm text-slate-500">
                            {{ company.callback_at_label || 'No callback scheduled.' }}
                        </p>
                    </div>

                    <div class="grid gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Status</label>
                            <select v-model="classificationForm.status_id" class="williambeeler-select mt-2">
                                <option v-for="status in statusOptions" :key="status.id" :value="status.id">
                                    {{ status.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Relationship</label>
                            <select v-model="classificationForm.relationship_id" class="williambeeler-select mt-2">
                                <option v-for="relationship in relationshipOptions" :key="relationship.id" :value="relationship.id">
                                    {{ relationship.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" class="williambeeler-button-secondary" :disabled="savingClassification" @click="saveClassification">
                                {{ savingClassification ? 'Saving...' : 'Save Classification' }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="williambeeler-card p-6 md:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Primary Contact</h2>

                    <div class="flex items-center gap-2">
                        <template v-if="isEditingPrimaryContact">
                            <button type="button" class="williambeeler-button-secondary" :disabled="savingPrimaryContact" @click="cancelPrimaryContactEdit">
                                Cancel
                            </button>
                            <button type="button" class="williambeeler-button-primary" :disabled="savingPrimaryContact" @click="savePrimaryContact">
                                {{ savingPrimaryContact ? 'Saving...' : 'Save' }}
                            </button>
                        </template>
                        <button v-else type="button" class="williambeeler-button-secondary" @click="isEditingPrimaryContact = true">
                            Edit
                        </button>
                    </div>
                </div>

                <div v-if="isEditingPrimaryContact" class="mt-6 grid gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Name</label>
                        <input v-model="primaryContactForm.poc_name" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Email</label>
                        <input v-model="primaryContactForm.poc_email" type="email" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Phone</label>
                        <input v-model="primaryContactForm.poc_phone" type="text" class="williambeeler-input mt-2">
                    </div>
                </div>

                <dl v-else class="mt-6 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Name</dt>
                        <dd class="mt-2 text-base text-slate-800">{{ company.poc_name || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email</dt>
                        <dd class="mt-2 text-base text-slate-800">{{ company.poc_email || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</dt>
                        <dd class="mt-2 text-base text-slate-800">{{ company.poc_phone || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="williambeeler-card p-6 md:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Address</h2>

                    <div class="flex items-center gap-2">
                        <template v-if="isEditingAddress">
                            <button type="button" class="williambeeler-button-secondary" :disabled="savingAddress" @click="cancelAddressEdit">
                                Cancel
                            </button>
                            <button type="button" class="williambeeler-button-primary" :disabled="savingAddress" @click="saveAddress">
                                {{ savingAddress ? 'Saving...' : 'Save' }}
                            </button>
                        </template>
                        <button v-else type="button" class="williambeeler-button-secondary" @click="isEditingAddress = true">
                            Edit
                        </button>
                    </div>
                </div>

                <div v-if="isEditingAddress" class="mt-6 grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Address Line 1</label>
                        <input v-model="addressForm.address_line1" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Address Line 2</label>
                        <input v-model="addressForm.address_line2" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">City</label>
                        <input v-model="addressForm.city" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">State</label>
                        <input v-model="addressForm.state" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                        <input v-model="addressForm.postal_code" type="text" class="williambeeler-input mt-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Country</label>
                        <input v-model="addressForm.country" type="text" class="williambeeler-input mt-2">
                    </div>
                </div>

                <div v-else class="mt-6 whitespace-pre-line text-base text-slate-700">
                    {{ addressLabel }}
                </div>
            </section>

            <section class="williambeeler-card p-6 md:p-8">
                <div class="border-b border-slate-200 pb-5">
                    <h2 class="text-2xl text-slate-900">Phone Calls</h2>
                </div>

                <form class="mt-6" @submit.prevent="submitPhoneCall">
                    <label class="block text-sm font-semibold text-slate-700">Call Date</label>
                    <input v-model="phoneCallForm.called_at" type="datetime-local" class="williambeeler-input mt-2" required>

                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-slate-700">Call Notes</label>
                        <RichTextEditor input-id="company-call-editor" v-model="phoneCallForm.notes" placeholder="Log the conversation details and next step" />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="williambeeler-button-primary" :disabled="savingPhoneCall">
                            {{ savingPhoneCall ? 'Saving...' : 'Log Phone Call' }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 space-y-4">
                    <article v-for="phoneCall in company.phone_calls" :key="phoneCall.id" class="rounded-2xl border border-slate-200 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ phoneCall.called_at_label }}</p>
                        <div class="williambeeler-rich-text mt-3" v-html="phoneCall.notes"></div>
                    </article>
                    <p v-if="company.phone_calls.length === 0" class="text-sm text-slate-500">No phone calls logged for this company.</p>
                </div>
            </section>
        </div>
    </div>
</template>

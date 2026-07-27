<script setup>
import RichTextEditor from '@crm/Components/RichTextEditor.vue'
import { computed } from 'vue'

const props = defineProps({
    form: {
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
    tagOptions: {
        type: Array,
        required: true,
    },
    campaignOptions: {
        type: Array,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    isEditing: {
        type: Boolean,
        default: false,
    },
})

const tagPlaceholder = computed(() => {
    if (props.tagOptions.length === 0) {
        return 'SEO, Warm Lead, Franchise'
    }

    return props.tagOptions.slice(0, 4).map((tag) => tag.name).join(', ')
})

const campaignPlaceholder = computed(() => {
    if (props.campaignOptions.length === 0) {
        return 'Spring Outreach, Priority Renewal'
    }

    return props.campaignOptions.slice(0, 3).map((campaign) => campaign.name).join(', ')
})

const noteValue = computed({
    get() {
        return props.isEditing ? props.form.new_note : props.form.initial_note
    },
    set(value) {
        if (props.isEditing) {
            props.form.new_note = value

            return
        }

        props.form.initial_note = value
    },
})

function updateCheckbox(field, event) {
    props.form[field] = event.target.checked
}
</script>

<template>
    <form class="space-y-8" @submit.prevent="$emit('submit')">
        <section class="williambeeler-card p-6 md:p-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl text-slate-900">
                    Company Information
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    Core account details, callback status, and primary classification.
                </p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        Company Name
                    </label>
                    <input v-model="form.name" type="text" class="williambeeler-input mt-2" required>
                    <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Alias</label>
                    <input v-model="form.alias" type="text" class="williambeeler-input mt-2">
                    <p v-if="form.errors.alias" class="mt-2 text-sm text-red-600">{{ form.errors.alias }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Website</label>
                    <input v-model="form.website" type="url" class="williambeeler-input mt-2">
                    <p v-if="form.errors.website" class="mt-2 text-sm text-red-600">{{ form.errors.website }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Company Email</label>
                    <input v-model="form.email" type="email" class="williambeeler-input mt-2">
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone</label>
                    <input v-model="form.phone" type="text" class="williambeeler-input mt-2">
                    <p v-if="form.errors.phone" class="mt-2 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                        <input
                            :checked="form.needs_callback"
                            type="checkbox"
                            class="rounded border-slate-300 text-cyan-500 focus:ring-cyan-400"
                            @change="updateCheckbox('needs_callback', $event)"
                        >
                        Call back
                    </label>
                </div>

                <div v-if="form.needs_callback">
                    <label class="block text-sm font-semibold text-slate-700">Call Back Date</label>
                    <input v-model="form.callback_at" type="datetime-local" class="williambeeler-input mt-2">
                    <p v-if="form.errors.callback_at" class="mt-2 text-sm text-red-600">{{ form.errors.callback_at }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Lead Source</label>
                    <input v-model="form.source" type="text" class="williambeeler-input mt-2" placeholder="Website form, referral, cold outreach">
                    <p v-if="form.errors.source" class="mt-2 text-sm text-red-600">{{ form.errors.source }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Status</label>
                    <select v-model="form.status_id" class="williambeeler-select mt-2">
                        <option value="">Select status</option>
                        <option v-for="status in statusOptions" :key="status.id" :value="status.id">
                            {{ status.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.status_id" class="mt-2 text-sm text-red-600">{{ form.errors.status_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Relationship</label>
                    <select v-model="form.relationship_id" class="williambeeler-select mt-2">
                        <option value="">Select relationship</option>
                        <option v-for="relationship in relationshipOptions" :key="relationship.id" :value="relationship.id">
                            {{ relationship.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.relationship_id" class="mt-2 text-sm text-red-600">{{ form.errors.relationship_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Tags</label>
                    <input v-model="form.tags" type="text" class="williambeeler-input mt-2" :placeholder="tagPlaceholder">
                    <p class="mt-2 text-xs text-slate-500">Separate tags with commas.</p>
                    <p v-if="form.errors.tags" class="mt-2 text-sm text-red-600">{{ form.errors.tags }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Campaigns</label>
                    <input v-model="form.campaigns" type="text" class="williambeeler-input mt-2" :placeholder="campaignPlaceholder">
                    <p class="mt-2 text-xs text-slate-500">Separate campaigns with commas.</p>
                    <p v-if="form.errors.campaigns" class="mt-2 text-sm text-red-600">{{ form.errors.campaigns }}</p>
                </div>
            </div>
        </section>

        <section class="williambeeler-card p-6 md:p-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl text-slate-900">Primary Contact</h2>
                <p class="mt-2 text-sm text-slate-500">The best person to reach inside this account.</p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Name</label>
                    <input v-model="form.poc_name" type="text" class="williambeeler-input mt-2">
                    <p v-if="form.errors.poc_name" class="mt-2 text-sm text-red-600">{{ form.errors.poc_name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Email</label>
                    <input v-model="form.poc_email" type="email" class="williambeeler-input mt-2">
                    <p v-if="form.errors.poc_email" class="mt-2 text-sm text-red-600">{{ form.errors.poc_email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Phone</label>
                    <input v-model="form.poc_phone" type="text" class="williambeeler-input mt-2">
                    <p v-if="form.errors.poc_phone" class="mt-2 text-sm text-red-600">{{ form.errors.poc_phone }}</p>
                </div>
            </div>
        </section>

        <section class="williambeeler-card p-6 md:p-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl text-slate-900">Address</h2>
                <p class="mt-2 text-sm text-slate-500">Mailing details and service area context.</p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Address Line 1</label>
                    <input v-model="form.address_line1" type="text" class="williambeeler-input mt-2">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Address Line 2</label>
                    <input v-model="form.address_line2" type="text" class="williambeeler-input mt-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">City</label>
                    <input v-model="form.city" type="text" class="williambeeler-input mt-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">State</label>
                    <input v-model="form.state" type="text" class="williambeeler-input mt-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Postal Code</label>
                    <input v-model="form.postal_code" type="text" class="williambeeler-input mt-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Country</label>
                    <input v-model="form.country" type="text" class="williambeeler-input mt-2">
                </div>
            </div>
        </section>

        <section class="williambeeler-card p-6 md:p-8">
            <div class="border-b border-slate-200 pb-5">
                <h2 class="text-2xl text-slate-900">Opportunity Context</h2>
                <p class="mt-2 text-sm text-slate-500">Score the account and save the reasoning behind it.</p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Opportunity Score Index</label>
                    <input v-model="form.opportunity_score_index" type="number" min="0" max="100" step="1" class="williambeeler-input mt-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Meta (JSON)</label>
                    <textarea v-model="form.meta" rows="5" class="williambeeler-input mt-2 font-mono text-sm" placeholder='{"key":"value"}'></textarea>
                    <p v-if="form.errors.meta" class="mt-2 text-sm text-red-600">{{ form.errors.meta }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Opportunity Summary</label>
                    <textarea v-model="form.opportunity_summary" rows="5" class="williambeeler-input mt-2"></textarea>
                    <p v-if="form.errors.opportunity_summary" class="mt-2 text-sm text-red-600">{{ form.errors.opportunity_summary }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700">
                        {{ isEditing ? 'Add Note' : 'Initial Note' }}
                    </label>
                    <RichTextEditor
                        :input-id="isEditing ? 'company-new-note' : 'company-initial-note'"
                        v-model="noteValue"
                        :placeholder="isEditing ? 'Add a new note to this company' : 'Start the account record with context or next steps'"
                    />
                    <p
                        v-if="!isEditing && form.errors.initial_note"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.initial_note }}
                    </p>
                    <p
                        v-if="isEditing && form.errors.new_note"
                        class="mt-2 text-sm text-red-600"
                    >
                        {{ form.errors.new_note }}
                    </p>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="submit" class="williambeeler-button-primary" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : submitLabel }}
            </button>
        </div>
    </form>
</template>

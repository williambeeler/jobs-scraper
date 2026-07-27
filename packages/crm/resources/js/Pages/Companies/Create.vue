<script setup>
import CompanyForm from '@crm/Components/CompanyForm.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
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
})

const form = useForm({
    name: '',
    alias: '',
    website: '',
    email: '',
    phone: '',
    needs_callback: false,
    callback_at: '',
    poc_name: '',
    poc_email: '',
    poc_phone: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    source: '',
    status_id: '',
    relationship_id: '',
    opportunity_score_index: '',
    opportunity_summary: '',
    meta: '',
    tags: '',
    campaigns: '',
    initial_note: '',
})

function submit() {
    form.post(route('companies.store'))
}
</script>

<template>
    <Head title="Create Company" />

    <CrmShell
        title="Create Company"
        subtitle="Add a new account, capture the initial context, and route it into the right relationship and status."
    >
        <template #actions>
            <Link :href="route('companies.index')" class="williambeeler-button-secondary">
                Back to Companies
            </Link>
        </template>

        <CompanyForm
            :form="form"
            :status-options="statusOptions"
            :relationship-options="relationshipOptions"
            :tag-options="tagOptions"
            :campaign-options="campaignOptions"
            submit-label="Save Company"
            @submit="submit"
        />
    </CrmShell>
</template>

<script setup>
import CompanyForm from '@crm/Components/CompanyForm.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    company: {
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
})

const form = useForm({
    name: props.company.name ?? '',
    alias: props.company.alias ?? '',
    website: props.company.website ?? '',
    email: props.company.email ?? '',
    phone: props.company.phone ?? '',
    needs_callback: props.company.needs_callback ?? false,
    callback_at: props.company.callback_at ?? '',
    poc_name: props.company.poc_name ?? '',
    poc_email: props.company.poc_email ?? '',
    poc_phone: props.company.poc_phone ?? '',
    address_line1: props.company.address_line1 ?? '',
    address_line2: props.company.address_line2 ?? '',
    city: props.company.city ?? '',
    state: props.company.state ?? '',
    postal_code: props.company.postal_code ?? '',
    country: props.company.country ?? '',
    source: props.company.source ?? '',
    status_id: props.company.status_id ?? '',
    relationship_id: props.company.relationship_id ?? '',
    opportunity_score_index: props.company.opportunity_score_index ?? '',
    opportunity_summary: props.company.opportunity_summary ?? '',
    meta: props.company.meta ?? '',
    tags: props.company.tags ?? '',
    campaigns: props.company.campaigns ?? '',
    new_note: '',
})

function submit() {
    form.put(route('companies.update', props.company.id))
}
</script>

<template>
    <Head :title="`Edit ${company.name}`" />

    <CrmShell
        :title="`Edit ${company.name}`"
        subtitle="Update company details, adjust classification, and append new context without losing the account history."
    >
        <template #actions>
            <div class="flex flex-wrap gap-3">
                <Link :href="route('companies.show', company.id)" class="williambeeler-button-secondary">
                    View Company
                </Link>
                <Link :href="route('companies.index')" class="williambeeler-button-secondary">
                    Back to Companies
                </Link>
            </div>
        </template>

        <CompanyForm
            :form="form"
            :status-options="statusOptions"
            :relationship-options="relationshipOptions"
            :tag-options="tagOptions"
            :campaign-options="campaignOptions"
            submit-label="Save Changes"
            :is-editing="true"
            @submit="submit"
        />
    </CrmShell>
</template>

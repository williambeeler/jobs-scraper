<script setup>
import CompanyDetailWorkspace from '@crm/Components/CompanyDetailWorkspace.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

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
})

const page = usePage()
const successMessage = computed(() => page.props.flash?.success)

function archiveCompany() {
    if (! window.confirm(`Archive ${props.company.name}?`)) {
        return
    }

    router.delete(props.company.delete_url)
}
</script>

<template>
    <Head :title="company.name" />

    <CrmShell
        :title="company.name"
        subtitle="Company details, notes, phone call history, and classification controls in one workspace."
    >
        <template #actions>
            <div class="flex flex-wrap gap-3">
                <Link :href="company.edit_url" class="williambeeler-button-secondary">
                    Edit Company
                </Link>
                <button type="button" class="williambeeler-button-danger" @click="archiveCompany">
                    Archive
                </button>
            </div>
        </template>

        <div
            v-if="successMessage"
            class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
        >
            {{ successMessage }}
        </div>

        <CompanyDetailWorkspace
            :company-data="company"
            :status-options="statusOptions"
            :relationship-options="relationshipOptions"
        />
    </CrmShell>
</template>

<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

const props = defineProps({
    activeTab: {
        type: String,
        required: true,
    },
    mode: {
        type: String,
        required: true,
    },
    businessProfiles: {
        type: Array,
        required: true,
    },
    selectedBusiness: {
        type: Object,
        default: null,
    },
    selectedLabelBusiness: {
        type: Object,
        default: null,
    },
})

const isCreateMode = computed(() => props.mode === 'create')

const form = useForm({
    business_name: props.selectedBusiness?.business_name || '',
    street_line_1: props.selectedBusiness?.street_line_1 || '',
    street_line_2: props.selectedBusiness?.street_line_2 || '',
    city: props.selectedBusiness?.city || '',
    state: props.selectedBusiness?.state || '',
    postal_code: props.selectedBusiness?.postal_code || '',
    country: props.selectedBusiness?.country || '',
    is_default: props.selectedBusiness?.is_default || false,
})

watch(
    () => [props.selectedBusiness, props.mode],
    () => {
        form.defaults({
            business_name: props.selectedBusiness?.business_name || '',
            street_line_1: props.selectedBusiness?.street_line_1 || '',
            street_line_2: props.selectedBusiness?.street_line_2 || '',
            city: props.selectedBusiness?.city || '',
            state: props.selectedBusiness?.state || '',
            postal_code: props.selectedBusiness?.postal_code || '',
            country: props.selectedBusiness?.country || '',
            is_default: props.selectedBusiness?.is_default || false,
        })

        form.reset()
        form.clearErrors()
    },
)

const tabs = computed(() => [
    {
        label: 'Business Address',
        href: route('my-business.index', { tab: 'address' }),
        active: props.activeTab === 'address',
    },
    {
        label: 'Labels',
        href: route('my-business.index', { tab: 'labels' }),
        active: props.activeTab === 'labels',
    },
])

function selectBusiness(event) {
    router.get(route('my-business.index'), {
        tab: 'address',
        mode: 'edit',
        business_id: event.target.value,
    }, {
        preserveScroll: true,
    })
}

function submit() {
    if (isCreateMode.value) {
        form.post(route('my-business.address.store'))

        return
    }

    form.patch(route('my-business.address.update', props.selectedBusiness.id))
}

function deleteBusiness() {
    if (!props.selectedBusiness || !window.confirm(`Delete ${props.selectedBusiness.business_name}?`)) {
        return
    }

    router.delete(route('my-business.destroy', props.selectedBusiness.id))
}
</script>

<template>
    <Head title="My Business" />

    <CrmShell
        title="My Business"
        subtitle="Manage legal entities, mailing profiles, and printable business labels."
    >
        <div class="space-y-6">
            <FlashMessages />

            <section class="williambeeler-card overflow-hidden">
                <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 py-4 sm:px-6">
                    <Link
                        v-for="tab in tabs"
                        :key="tab.label"
                        :href="tab.href"
                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                        :class="tab.active
                            ? 'bg-slate-950 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
                    >
                        {{ tab.label }}
                    </Link>
                </div>

                <div class="px-4 py-5 sm:px-6">
                    <div v-if="activeTab === 'address'" class="space-y-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl text-slate-900">
                                    Business Profiles
                                </h2>
                                <p class="mt-2 text-sm text-slate-500">
                                    Choose an existing profile to edit, or add a new DBA or mailing address.
                                </p>
                            </div>

                            <Link
                                :href="route('my-business.index', { tab: 'address', mode: 'create' })"
                                class="williambeeler-button-secondary"
                            >
                                Add New Business
                            </Link>
                        </div>

                        <div v-if="businessProfiles.length > 0" class="grid gap-3 lg:max-w-md">
                            <label class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">
                                Business
                            </label>
                            <select class="williambeeler-select" :value="selectedBusiness?.id || ''" @change="selectBusiness">
                                <option
                                    v-for="business in businessProfiles"
                                    :key="business.id"
                                    :value="business.id"
                                >
                                    {{ business.label }}
                                </option>
                            </select>
                        </div>

                        <form class="grid gap-4" @submit.prevent="submit">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Business Name</label>
                                    <input v-model="form.business_name" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.business_name" class="mt-2 text-sm text-rose-600">{{ form.errors.business_name }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Street Address</label>
                                    <input v-model="form.street_line_1" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.street_line_1" class="mt-2 text-sm text-rose-600">{{ form.errors.street_line_1 }}</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Address Line 2</label>
                                    <input v-model="form.street_line_2" type="text" class="williambeeler-input" />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">City</label>
                                    <input v-model="form.city" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.city" class="mt-2 text-sm text-rose-600">{{ form.errors.city }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">State / Province</label>
                                    <input v-model="form.state" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.state" class="mt-2 text-sm text-rose-600">{{ form.errors.state }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Postal Code</label>
                                    <input v-model="form.postal_code" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.postal_code" class="mt-2 text-sm text-rose-600">{{ form.errors.postal_code }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Country</label>
                                    <input v-model="form.country" type="text" class="williambeeler-input" />
                                    <p v-if="form.errors.country" class="mt-2 text-sm text-rose-600">{{ form.errors.country }}</p>
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-500 focus:ring-cyan-400" />
                                Set as default business
                            </label>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="williambeeler-button-primary" :disabled="form.processing">
                                    {{ isCreateMode ? 'Add Business' : 'Save Changes' }}
                                </button>

                                <Link
                                    v-if="isCreateMode && businessProfiles.length > 0"
                                    :href="route('my-business.index', { tab: 'address' })"
                                    class="williambeeler-button-secondary"
                                >
                                    Cancel
                                </Link>

                                <button
                                    v-if="!isCreateMode && selectedBusiness"
                                    type="button"
                                    class="williambeeler-button-danger"
                                    @click="deleteBusiness"
                                >
                                    Delete Business
                                </button>
                            </div>
                        </form>
                    </div>

                    <div v-else class="space-y-6">
                        <div>
                            <h2 class="text-2xl text-slate-900">
                                Print Avery 94200 Labels
                            </h2>
                            <p class="mt-2 text-sm text-slate-500">
                                Choose a business profile and download a one page label sheet.
                            </p>
                        </div>

                        <p v-if="businessProfiles.length === 0" class="text-sm text-slate-500">
                            Add a business on the Business Address tab before printing labels.
                        </p>

                        <form
                            v-else
                            :action="route('my-business.labels.print')"
                            method="GET"
                            class="grid gap-4 lg:max-w-xl"
                        >
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Business</label>
                                <select name="business_id" class="williambeeler-select">
                                    <option
                                        v-for="business in businessProfiles"
                                        :key="business.id"
                                        :value="business.id"
                                        :selected="selectedLabelBusiness?.id === business.id"
                                    >
                                        {{ business.label }}
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="williambeeler-button-primary w-full sm:w-auto">
                                Print Labels
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </CrmShell>
</template>

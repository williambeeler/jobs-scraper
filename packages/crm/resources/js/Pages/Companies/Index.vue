<script setup>
import CompanyDetailWorkspace from '@crm/Components/CompanyDetailWorkspace.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    statusOptions: {
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
    totalCompanies: {
        type: Number,
        required: true,
    },
    currentSearchSaved: {
        type: Boolean,
        required: true,
    },
    createUrl: {
        type: String,
        required: true,
    },
    bulkActionUrl: {
        type: String,
        required: true,
    },
    dataUrl: {
        type: String,
        required: true,
    },
})

const page = usePage()
const companies = ref([])
const isLoading = ref(false)
const search = ref(props.filters.search || '')
const statusId = ref(props.filters.status_id || '')
const tagFilter = ref(props.filters.tag_filter || '')
const campaignFilter = ref(props.filters.campaign_filter || '')
const createdRange = ref(props.filters.created_range || 'all')
const perPage = ref(props.filters.per_page || '20')
const sortField = ref(props.filters.sort || 'name')
const sortDirection = ref(props.filters.direction || 'asc')
const currentPage = ref(Number(props.filters.page || 1))
const showBulkActions = ref(false)
const openActionMenuCompanyId = ref(null)
const isCompanyModalOpen = ref(false)
const isCompanyModalLoading = ref(false)
const modalCompanyPayload = ref(null)
const activeModalCompanyId = ref(null)
const selectedCompanyIds = ref([])
const showFilters = ref(false)
const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: null,
    to: null,
})

let searchDebounce = null

const bulkActionForm = useForm({
    bulk_action: '',
    scope: 'selected',
    companies: [],
    tags: [],
    campaigns: [],
    search_term: '',
    status_id: '',
    tag_filter: '',
    campaign_filter: '',
    created_range: 'all',
})

const successMessage = computed(() => page.props.flash?.success)

const pageNumbers = computed(() => {
    const total = meta.value.last_page
    const pageNumberList = []
    const start = Math.max(1, currentPage.value - 2)
    const end = Math.min(total, currentPage.value + 2)

    for (let pageNumber = start; pageNumber <= end; pageNumber += 1) {
        pageNumberList.push(pageNumber)
    }

    return pageNumberList
})

const visiblePaginationItems = computed(() => {
    const totalPages = meta.value.last_page

    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, index) => ({
            type: 'page',
            value: index + 1,
        }))
    }

    const items = [
        { type: 'page', value: 1 },
    ]

    let start = Math.max(2, currentPage.value - 1)
    let end = Math.min(totalPages - 1, currentPage.value + 1)

    if (currentPage.value <= 3) {
        start = 2
        end = 4
    }

    if (currentPage.value >= totalPages - 2) {
        start = totalPages - 3
        end = totalPages - 1
    }

    if (start > 2) {
        items.push({ type: 'ellipsis', value: 'left-ellipsis' })
    }

    for (let pageNumber = start; pageNumber <= end; pageNumber += 1) {
        items.push({ type: 'page', value: pageNumber })
    }

    if (end < totalPages - 1) {
        items.push({ type: 'ellipsis', value: 'right-ellipsis' })
    }

    items.push({ type: 'page', value: totalPages })

    return items
})

const summaryLabel = computed(() => {
    if (meta.value.total === 0) {
        return 'No companies found.'
    }

    const from = meta.value.from ?? 1
    const to = meta.value.to ?? meta.value.total

    return `Showing ${from} to ${to} of ${meta.value.total} companies`
})

const hasActiveFilters = computed(() => (
    search.value !== ''
    || statusId.value !== ''
    || tagFilter.value !== ''
    || campaignFilter.value !== ''
    || createdRange.value !== 'all'
    || perPage.value !== '20'
))
const hasCompoundableFilters = computed(() => (
    search.value !== ''
    || tagFilter.value !== ''
    || campaignFilter.value !== ''
    || createdRange.value !== 'all'
))
const allVisibleCompaniesSelected = computed(() => companies.value.length > 0 && companies.value.every((company) => selectedCompanyIds.value.includes(company.id)))
const hasSelectedCompanies = computed(() => selectedCompanyIds.value.length > 0)

const activeModalCompanyIndex = computed(() => companies.value.findIndex((company) => company.id === activeModalCompanyId.value))
const previousModalCompany = computed(() => {
    if (activeModalCompanyIndex.value <= 0) {
        return null
    }

    return companies.value[activeModalCompanyIndex.value - 1]
})
const nextModalCompany = computed(() => {
    if (activeModalCompanyIndex.value === -1 || activeModalCompanyIndex.value >= companies.value.length - 1) {
        return null
    }

    return companies.value[activeModalCompanyIndex.value + 1]
})

async function fetchCompanies() {
    isLoading.value = true

    try {
        const response = await window.axios.get(props.dataUrl, {
            params: {
                search: search.value,
                status_id: statusId.value,
                tag_filter: tagFilter.value,
                campaign_filter: campaignFilter.value,
                created_range: createdRange.value,
                per_page: perPage.value,
                sort: sortField.value,
                direction: sortDirection.value,
                page: currentPage.value,
            },
        })

        companies.value = response.data.data
        meta.value = response.data.meta
        selectedCompanyIds.value = selectedCompanyIds.value.filter((companyId) => companies.value.some((company) => company.id === companyId))
        syncUrl()
        window.history.scrollRestoration = 'manual'
    } finally {
        isLoading.value = false
    }
}

function syncUrl() {
    const url = new URL(window.location.href)
    const params = {
        search: search.value,
        status_id: statusId.value,
        tag_filter: tagFilter.value,
        campaign_filter: campaignFilter.value,
        created_range: createdRange.value,
        per_page: perPage.value,
        sort: sortField.value,
        direction: sortDirection.value,
        page: String(currentPage.value),
    }

    Object.entries(params).forEach(([key, value]) => {
        if (! value || value === 'all') {
            url.searchParams.delete(key)
            return
        }

        if (key === 'per_page' && value === '20') {
            url.searchParams.delete(key)
            return
        }

        if (key === 'sort' && value === 'name') {
            url.searchParams.delete(key)
            return
        }

        if (key === 'direction' && value === 'asc') {
            url.searchParams.delete(key)
            return
        }

        if (key === 'page' && value === '1') {
            url.searchParams.delete(key)
            return
        }

        url.searchParams.set(key, value)
    })

    window.history.replaceState({}, '', `${url.pathname}${url.search}`)
}

function resetToFirstPageAndFetch() {
    currentPage.value = 1
    fetchCompanies()
}

function goToPage(pageNumber) {
    if (pageNumber < 1 || pageNumber > meta.value.last_page || pageNumber === currentPage.value) {
        return
    }

    currentPage.value = pageNumber
    fetchCompanies()
}

function goToFirstPage() {
    goToPage(1)
}

function goToLastPage() {
    goToPage(meta.value.last_page)
}

function toggleSort(field) {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDirection.value = 'asc'
    }

    resetToFirstPageAndFetch()
}

function sortIndicator(field) {
    if (sortField.value !== field) {
        return '↕'
    }

    return sortDirection.value === 'asc' ? '↑' : '↓'
}

function filterByTag(tag) {
    tagFilter.value = tag
    resetToFirstPageAndFetch()
}

function filterByCampaign(campaign) {
    campaignFilter.value = campaign
    resetToFirstPageAndFetch()
}

function filterByStatus(company) {
    if (! company.status_id) {
        return
    }

    if (! hasCompoundableFilters.value) {
        search.value = ''
        tagFilter.value = ''
        campaignFilter.value = ''
        createdRange.value = 'all'
    }

    statusId.value = String(company.status_id)
    resetToFirstPageAndFetch()
}

function statusBadgeClass(status) {
    const normalizedStatus = (status || '').toLowerCase()

    if (normalizedStatus.includes('customer') || normalizedStatus.includes('active')) {
        return 'bg-emerald-100 text-emerald-700'
    }

    if (normalizedStatus.includes('lead') || normalizedStatus.includes('new')) {
        return 'bg-cyan-100 text-cyan-700'
    }

    if (normalizedStatus.includes('archive') || normalizedStatus.includes('lost')) {
        return 'bg-rose-100 text-rose-700'
    }

    return 'bg-slate-200 text-slate-700'
}

function scoreBadgeClass(score) {
    if (score === null || score === undefined || String(score).toLowerCase() === 'n/a') {
        return 'bg-slate-200/80 text-slate-600'
    }

    const numericScore = Math.min(100, Math.max(0, Number(score)))

    if (Number.isNaN(numericScore)) {
        return 'bg-slate-200/80 text-slate-600'
    }

    const bucket = Math.min(9, Math.max(0, Math.floor(numericScore / 10)))
    const classes = [
        'bg-red-600/80 text-white',
        'bg-red-500/80 text-white',
        'bg-orange-600/80 text-white',
        'bg-orange-500/80 text-white',
        'bg-amber-500/80 text-slate-950',
        'bg-yellow-400/80 text-slate-950',
        'bg-lime-400/80 text-slate-950',
        'bg-lime-500/80 text-slate-950',
        'bg-emerald-500/80 text-white',
        'bg-emerald-600/80 text-white',
    ]

    return classes[bucket]
}

function archiveCompany(company) {
    if (! window.confirm(`Archive ${company.name}?`)) {
        return
    }

    router.delete(company.delete_url, {
        preserveScroll: true,
        onSuccess: () => {
            closeCompanyModal()
            fetchCompanies()
        },
    })
}

function togglePanel(panel) {
    if (panel === 'filters') {
        const nextState = !showFilters.value

        showFilters.value = nextState
        showBulkActions.value = false

        return
    }

    const nextState = !showBulkActions.value

    showBulkActions.value = nextState
    showFilters.value = false
}

function toggleCompanySelection(companyId) {
    if (selectedCompanyIds.value.includes(companyId)) {
        selectedCompanyIds.value = selectedCompanyIds.value.filter((id) => id !== companyId)
        return
    }

    selectedCompanyIds.value = [...selectedCompanyIds.value, companyId]
}

function toggleSelectAllVisibleCompanies() {
    if (allVisibleCompaniesSelected.value) {
        selectedCompanyIds.value = []
        return
    }

    selectedCompanyIds.value = companies.value.map((company) => company.id)
}

function submitBulkAction() {
    bulkActionForm.transform((data) => ({
        ...data,
        companies: selectedCompanyIds.value,
        search_term: search.value,
        status_id: statusId.value,
        tag_filter: tagFilter.value,
        campaign_filter: campaignFilter.value,
        created_range: createdRange.value,
    })).post(props.bulkActionUrl, {
        preserveScroll: true,
        onSuccess: () => {
            selectedCompanyIds.value = []
            showBulkActions.value = false
            bulkActionForm.reset('bulk_action', 'tags', 'campaigns')
            bulkActionForm.scope = 'selected'
            fetchCompanies()
        },
    })
}

function formatWebsiteLabel(website) {
    if (! website) {
        return 'No website'
    }

    try {
        const parsedUrl = new URL(website)

        return parsedUrl.hostname.replace(/^www\./, '')
    } catch {
        return website.replace(/^https?:\/\//, '').replace(/^www\./, '')
    }
}

function toggleCompanyActionMenu(companyId) {
    openActionMenuCompanyId.value = openActionMenuCompanyId.value === companyId
        ? null
        : companyId
}

async function openCompanyModal(company) {
    openActionMenuCompanyId.value = null
    isCompanyModalOpen.value = true
    isCompanyModalLoading.value = true
    modalCompanyPayload.value = null
    activeModalCompanyId.value = company.id

    try {
        const response = await window.axios.get(company.modal_url, {
            headers: {
                Accept: 'application/json',
            },
        })

        modalCompanyPayload.value = response.data
    } finally {
        isCompanyModalLoading.value = false
    }
}

function closeCompanyModal() {
    isCompanyModalOpen.value = false
    isCompanyModalLoading.value = false
    modalCompanyPayload.value = null
    activeModalCompanyId.value = null
}

function goToAdjacentCompany(direction) {
    const targetCompany = direction === 'previous'
        ? previousModalCompany.value
        : nextModalCompany.value

    if (! targetCompany) {
        return
    }

    openCompanyModal(targetCompany)
}

function closeMenusOnOutsideClick(event) {
    if (! event.target.closest('[data-company-action-menu]')) {
        openActionMenuCompanyId.value = null
    }
}

function closeModalOnEscape(event) {
    if (event.key === 'Escape') {
        openActionMenuCompanyId.value = null

        if (isCompanyModalOpen.value) {
            closeCompanyModal()
        }
    }
}

function resetFilters() {
    search.value = ''
    statusId.value = ''
    tagFilter.value = ''
    campaignFilter.value = ''
    createdRange.value = 'all'
    perPage.value = '20'
    sortField.value = 'name'
    sortDirection.value = 'asc'
    showFilters.value = false

    resetToFirstPageAndFetch()
}

watch(search, () => {
    window.clearTimeout(searchDebounce)
    searchDebounce = window.setTimeout(() => {
        resetToFirstPageAndFetch()
    }, 250)
})

watch([statusId, tagFilter, campaignFilter, createdRange, perPage], () => {
    resetToFirstPageAndFetch()
})

onMounted(() => {
    document.addEventListener('click', closeMenusOnOutsideClick)
    document.addEventListener('keydown', closeModalOnEscape)
    fetchCompanies()
})

onBeforeUnmount(() => {
    document.removeEventListener('click', closeMenusOnOutsideClick)
    document.removeEventListener('keydown', closeModalOnEscape)
})
</script>

<template>
    <Head title="Companies" />

    <CrmShell
        title="Companies"
        subtitle="Track accounts, assign tags, and manage outreach priorities."
    >
        <template #actions>
            <Link :href="createUrl" class="williambeeler-button-primary">
                Add Company
            </Link>
        </template>

        <section class="williambeeler-card overflow-hidden p-6 md:p-8">
            <div
                v-if="successMessage"
                class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
            >
                {{ successMessage }}
            </div>

            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl text-slate-900">
                            Company Directory
                        </h2>
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                            {{ totalCompanies }}
                        </span>
                        <span
                            v-if="currentSearchSaved"
                            class="inline-flex rounded-full bg-cyan-50 px-3 py-1 text-sm font-semibold text-cyan-700"
                        >
                            Saved search
                        </span>
                    </div>
                    <p class="mt-2 text-base text-slate-500">
                        Search, filter, and browse companies with server side pagination.
                    </p>
                </div>

                <div class="text-sm text-slate-500">
                    {{ summaryLabel }}
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-[5px] border px-4 py-2 text-sm font-semibold transition"
                        :class="showFilters
                            ? 'border-cyan-300 bg-cyan-50 text-cyan-800'
                            : 'border-slate-300 bg-white text-slate-900 hover:bg-slate-50'"
                        @click="togglePanel('filters')"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h16" />
                            <path d="M4 17h16" />
                            <path d="M4 12h16" />
                            <circle cx="9" cy="7" r="2.2" fill="white" />
                            <circle cx="15" cy="12" r="2.2" fill="white" />
                            <circle cx="11" cy="17" r="2.2" fill="white" />
                        </svg>
                        <span>Filters</span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-[5px] border px-4 py-2 text-sm font-semibold transition"
                        :class="showBulkActions
                            ? 'border-cyan-300 bg-cyan-50 text-cyan-800'
                            : 'border-slate-300 bg-white text-slate-900 hover:bg-slate-50'"
                        @click="togglePanel('bulk-actions')"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h10" />
                            <path d="M4 12h16" />
                            <path d="M4 17h12" />
                            <path d="M18 5l2 2 4-4" />
                        </svg>
                        <span>Bulk Actions</span>
                    </button>
                </div>

                <div v-if="hasActiveFilters && !showFilters" class="text-sm font-semibold text-slate-500">
                    Filters applied
                </div>
            </div>

            <div v-if="showFilters" class="mt-6 rounded-[1.75rem] border border-slate-200 bg-slate-50/65 p-5 md:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Search
                        </label>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search companies, contacts, websites, or emails"
                            class="williambeeler-input mt-2"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Status
                        </label>
                        <select v-model="statusId" class="williambeeler-select mt-2">
                            <option value="">
                                All statuses
                            </option>
                            <option
                                v-for="status in statusOptions"
                                :key="status.id"
                                :value="status.id"
                            >
                                {{ status.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Tag
                        </label>
                        <select v-model="tagFilter" class="williambeeler-select mt-2">
                            <option value="">
                                All tags
                            </option>
                            <option
                                v-for="tag in tagOptions"
                                :key="tag.name"
                                :value="tag.name"
                            >
                                {{ tag.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Campaign
                        </label>
                        <select v-model="campaignFilter" class="williambeeler-select mt-2">
                            <option value="">
                                All campaigns
                            </option>
                            <option
                                v-for="campaign in campaignOptions"
                                :key="campaign.name"
                                :value="campaign.name"
                            >
                                {{ campaign.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Created
                        </label>
                        <select v-model="createdRange" class="williambeeler-select mt-2">
                            <option value="all">
                                All time
                            </option>
                            <option value="last_30">
                                Last 30 days
                            </option>
                            <option value="last_90">
                                Last 90 days
                            </option>
                            <option value="this_year">
                                This year
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Per Page
                        </label>
                        <select v-model="perPage" class="williambeeler-select mt-2">
                            <option value="20">
                                20
                            </option>
                            <option value="50">
                                50
                            </option>
                            <option value="100">
                                100
                            </option>
                            <option value="250">
                                250
                            </option>
                            <option value="1000">
                                1000
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end xl:col-span-4 xl:justify-end">
                        <button
                            type="button"
                            class="williambeeler-button-secondary"
                            @click="resetFilters"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showBulkActions" class="mt-6 rounded-[1.75rem] border border-slate-200 bg-slate-50/65 p-5 md:p-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Scope
                        </label>
                        <select v-model="bulkActionForm.scope" class="williambeeler-select mt-2">
                            <option value="selected">
                                Selected companies
                            </option>
                            <option value="filtered">
                                All filtered companies
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Action
                        </label>
                        <select v-model="bulkActionForm.bulk_action" class="williambeeler-select mt-2">
                            <option value="">
                                Choose action
                            </option>
                            <option value="bulk_delete">
                                Delete
                            </option>
                            <option value="export_csv">
                                Export CSV
                            </option>
                            <option value="export_excel">
                                Export Excel
                            </option>
                            <option value="export_pdf">
                                Export PDF
                            </option>
                            <option value="export_vcard">
                                Export vCard
                            </option>
                            <option value="export_avery_pdf">
                                Export Avery PDF
                            </option>
                            <option value="bulk_campaign">
                                Add Campaign
                            </option>
                            <option value="bulk_tag">
                                Add Tag
                            </option>
                        </select>
                    </div>

                    <div v-if="bulkActionForm.bulk_action === 'bulk_campaign'">
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Campaigns
                        </label>
                        <select v-model="bulkActionForm.campaigns" multiple class="williambeeler-select mt-2 min-h-[7.5rem]">
                            <option
                                v-for="campaign in campaignOptions"
                                :key="campaign.name"
                                :value="campaign.name"
                            >
                                {{ campaign.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="bulkActionForm.bulk_action === 'bulk_tag'">
                        <label class="block text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
                            Tags
                        </label>
                        <select v-model="bulkActionForm.tags" multiple class="williambeeler-select mt-2 min-h-[7.5rem]">
                            <option
                                v-for="tag in tagOptions"
                                :key="tag.name"
                                :value="tag.name"
                            >
                                {{ tag.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-slate-500">
                        {{ hasSelectedCompanies ? `${selectedCompanyIds.length} selected on this page` : 'No companies selected on this page.' }}
                    </p>

                    <button
                        type="button"
                        class="williambeeler-button-secondary"
                        :disabled="bulkActionForm.processing || (bulkActionForm.scope === 'selected' && !hasSelectedCompanies) || !bulkActionForm.bulk_action"
                        @click="submitBulkAction"
                    >
                        Run Bulk Action
                    </button>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <input
                                        :checked="allVisibleCompaniesSelected"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-cyan-500 focus:ring-cyan-400"
                                        @change="toggleSelectAllVisibleCompanies"
                                    >
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <button type="button" class="inline-flex items-center gap-2 transition hover:text-slate-900" @click="toggleSort('name')">
                                        Company
                                        <span class="text-xs text-slate-400">{{ sortIndicator('name') }}</span>
                                    </button>
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    Tags
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    Campaigns
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <button type="button" class="inline-flex items-center gap-2 transition hover:text-slate-900" @click="toggleSort('status')">
                                        Status
                                        <span class="text-xs text-slate-400">{{ sortIndicator('status') }}</span>
                                    </button>
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <button type="button" class="inline-flex items-center gap-2 transition hover:text-slate-900" @click="toggleSort('opportunity_score_index')">
                                        Score
                                        <span class="text-xs text-slate-400">{{ sortIndicator('opportunity_score_index') }}</span>
                                    </button>
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <button type="button" class="inline-flex items-center gap-2 transition hover:text-slate-900" @click="toggleSort('city')">
                                        City
                                        <span class="text-xs text-slate-400">{{ sortIndicator('city') }}</span>
                                    </button>
                                </th>
                                <th class="px-5 py-4 text-left text-sm font-semibold text-slate-700">
                                    <button type="button" class="inline-flex items-center gap-2 transition hover:text-slate-900" @click="toggleSort('state')">
                                        State
                                        <span class="text-xs text-slate-400">{{ sortIndicator('state') }}</span>
                                    </button>
                                </th>
                                <th class="px-5 py-4 text-right text-sm font-semibold text-slate-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-if="isLoading">
                                <td colspan="9" class="px-5 py-10 text-center text-slate-500">
                                    Loading companies...
                                </td>
                            </tr>

                            <tr v-else-if="companies.length === 0">
                                <td colspan="9" class="px-5 py-10 text-center text-slate-500">
                                    No companies found for the current filters.
                                </td>
                            </tr>

                            <tr
                                v-for="company in companies"
                                :key="company.id"
                                class="transition-colors hover:bg-slate-50"
                            >
                                <td class="px-5 py-4 align-top">
                                    <input
                                        :checked="selectedCompanyIds.includes(company.id)"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-500 focus:ring-cyan-400"
                                        @change="toggleCompanySelection(company.id)"
                                    >
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <button
                                        type="button"
                                        class="text-left font-semibold text-[1.125rem] leading-6 text-slate-900 transition hover:text-cyan-700"
                                        @click="openCompanyModal(company)"
                                    >
                                        {{ company.name }}
                                    </button>
                                    <div class="mt-1">
                                        <a
                                            v-if="company.website"
                                            :href="company.website"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="text-sm text-slate-500 underline decoration-slate-300 underline-offset-4 transition hover:text-cyan-700"
                                        >
                                            {{ formatWebsiteLabel(company.website) }}
                                        </a>
                                        <span v-else class="text-sm text-slate-400">
                                            No website
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="tag in company.tags"
                                            :key="tag"
                                            type="button"
                                            class="rounded-[5px] bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-cyan-50 hover:text-cyan-700"
                                            @click="filterByTag(tag)"
                                        >
                                            {{ tag }}
                                        </button>
                                        <span v-if="company.tags.length === 0" class="text-sm text-slate-400">
                                            None
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="campaign in company.campaigns"
                                            :key="campaign"
                                            type="button"
                                            class="rounded-[5px] bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"
                                            @click="filterByCampaign(campaign)"
                                        >
                                            {{ campaign }}
                                        </button>
                                        <span v-if="company.campaigns.length === 0" class="text-sm text-slate-400">
                                            None
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <button
                                        type="button"
                                        class="inline-flex rounded-[5px] px-3 py-1 text-sm font-semibold transition hover:brightness-95"
                                        :class="statusBadgeClass(company.status)"
                                        @click="filterByStatus(company)"
                                    >
                                        {{ company.status || 'Unassigned' }}
                                    </button>
                                </td>
                                <td class="px-5 py-2 align-middle text-slate-700">
                                    <div
                                        class="flex min-h-[4.5rem] w-[4.5rem] items-center justify-center rounded-[5px] text-sm font-bold tracking-tight"
                                        :class="scoreBadgeClass(company.opportunity_score_index)"
                                    >
                                        {{ company.opportunity_score_index ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-slate-700">
                                    {{ company.city || '—' }}
                                </td>
                                <td class="px-5 py-4 align-top text-slate-700">
                                    {{ company.state || '—' }}
                                </td>
                                <td class="px-5 py-4 align-top text-right">
                                    <div class="relative inline-flex justify-end" data-company-action-menu>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-l-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            @click="openCompanyModal(company)"
                                        >
                                            View
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-r-xl border border-l-0 border-slate-300 bg-white px-3 text-slate-700 transition hover:bg-slate-50"
                                            @click.stop="toggleCompanyActionMenu(company.id)"
                                        >
                                            <span class="sr-only">Toggle company actions</span>
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 011.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>

                                        <div
                                            v-if="openActionMenuCompanyId === company.id"
                                            class="absolute right-0 top-[calc(100%+0.5rem)] z-10 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-[0_20px_45px_rgba(15,23,42,0.16)]"
                                        >
                                            <button
                                                type="button"
                                                class="block w-full px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                                @click="openCompanyModal(company)"
                                            >
                                                View
                                            </button>
                                            <Link
                                                :href="company.edit_url"
                                                class="block px-4 py-3 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                class="block w-full px-4 py-3 text-left text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                                @click="archiveCompany(company)"
                                            >
                                                Archive
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-slate-500">
                    {{ summaryLabel }}
                </p>

                <div v-if="meta.last_page > 1" class="inline-flex overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm">
                    <button
                        type="button"
                        class="inline-flex h-11 min-w-9 items-center justify-center border-r border-slate-300 px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:text-slate-300"
                        :disabled="currentPage === 1"
                        @click="goToFirstPage"
                    >
                        &laquo;
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-11 min-w-9 items-center justify-center border-r border-slate-300 px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:text-slate-300"
                        :disabled="currentPage === 1"
                        @click="goToPage(currentPage - 1)"
                    >
                        &lsaquo;
                    </button>

                    <template v-for="item in visiblePaginationItems" :key="item.value">
                        <button
                            v-if="item.type === 'page'"
                            type="button"
                            class="inline-flex h-11 min-w-9 items-center justify-center border-r border-slate-300 px-4 text-sm font-semibold transition"
                            :class="item.value === currentPage
                                ? 'bg-blue-600 text-white'
                                : 'bg-white text-blue-600 hover:bg-slate-50'"
                            @click="goToPage(item.value)"
                        >
                            {{ item.value.toLocaleString() }}
                        </button>

                        <span
                            v-else
                            class="inline-flex h-11 min-w-9 items-center justify-center border-r border-slate-300 px-4 text-sm font-semibold text-blue-600"
                        >
                            ...
                        </span>
                    </template>

                    <button
                        type="button"
                        class="inline-flex h-11 min-w-9 items-center justify-center border-r border-slate-300 px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:text-slate-300"
                        :disabled="currentPage === meta.last_page"
                        @click="goToPage(currentPage + 1)"
                    >
                        &rsaquo;
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-11 min-w-9 items-center justify-center px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:text-slate-300"
                        :disabled="currentPage === meta.last_page"
                        @click="goToLastPage"
                    >
                        &raquo;
                    </button>
                </div>
            </div>
        </section>

        <div
            v-if="isCompanyModalOpen"
            class="fixed inset-0 z-40 flex items-start justify-center bg-slate-950/45 px-6 py-8 md:px-12 lg:px-[60px]"
            @click.self="closeCompanyModal"
        >
            <div class="max-h-[calc(100vh-4rem)] w-full overflow-hidden rounded-[2rem] bg-white shadow-[0_32px_80px_rgba(15,23,42,0.28)]">
                <div class="flex items-start justify-between gap-6 border-b border-slate-200 px-6 py-5 md:px-8">
                    <div>
                        <h2 class="text-2xl text-slate-950">
                            {{ modalCompanyPayload?.company?.name || 'Company Details' }}
                        </h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Company details, notes, phone call history, and classification controls.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="williambeeler-button-secondary"
                            :disabled="!previousModalCompany || isCompanyModalLoading"
                            @click="goToAdjacentCompany('previous')"
                        >
                            &lt;
                        </button>
                        <button
                            type="button"
                            class="williambeeler-button-secondary"
                            :disabled="!nextModalCompany || isCompanyModalLoading"
                            @click="goToAdjacentCompany('next')"
                        >
                            &gt;
                        </button>
                        <Link
                            v-if="modalCompanyPayload?.company?.edit_url"
                            :href="modalCompanyPayload.company.edit_url"
                            class="williambeeler-button-secondary"
                        >
                            Edit Company
                        </Link>
                        <button type="button" class="williambeeler-button-secondary" @click="closeCompanyModal">
                            Close
                        </button>
                    </div>
                </div>

                <div class="max-h-[calc(100vh-9rem)] overflow-y-auto px-6 py-6 md:px-8">
                    <div v-if="isCompanyModalLoading" class="py-16 text-center text-slate-500">
                        Loading company details...
                    </div>

                    <CompanyDetailWorkspace
                        v-else-if="modalCompanyPayload"
                        :company-data="modalCompanyPayload.company"
                        :status-options="modalCompanyPayload.statusOptions"
                        :relationship-options="modalCompanyPayload.relationshipOptions"
                    />
                </div>
            </div>
        </div>
    </CrmShell>
</template>

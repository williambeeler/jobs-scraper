<script setup>
import FlashMessages from '@crm/Components/FlashMessages.vue'
import CrmShell from '@crm/Layouts/CrmShell.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    status: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <Head :title="status.name" />

    <CrmShell
        title="Status Details"
        subtitle="Review a system defined lifecycle label."
    >
        <template #actions>
            <Link :href="route('statuses.index')" class="williambeeler-button-secondary">
                Back
            </Link>
        </template>

        <div class="space-y-6">
            <FlashMessages />

            <section class="williambeeler-card px-4 py-5 sm:px-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="rounded-[1.5rem] border border-slate-200 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Name</p>
                        <p class="mt-3 text-3xl text-slate-900">{{ status.name }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Slug</p>
                        <p class="mt-3 text-2xl text-slate-900">{{ status.slug }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Description</p>
                        <p class="mt-3 text-base text-slate-600">{{ status.description || 'No description available.' }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Color</p>
                        <div class="mt-3 flex items-center gap-3">
                            <span
                                v-if="status.color"
                                class="inline-flex rounded-full px-3 py-1 text-sm font-semibold text-white"
                                :style="{ backgroundColor: status.color }"
                            >
                                {{ status.color }}
                            </span>
                            <span v-else class="text-slate-500">Not set</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </CrmShell>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    error: {
        type: String,
        default: '',
    },
    success: {
        type: String,
        default: '',
    },
})

const page = usePage()

const successMessage = computed(() => props.success || page.props.flash?.success || '')
const errorMessage = computed(() => props.error || page.props.flash?.error || '')
</script>

<template>
    <div v-if="successMessage || errorMessage" class="space-y-3">
        <div
            v-if="successMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            {{ successMessage }}
        </div>

        <div
            v-if="errorMessage"
            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"
        >
            {{ errorMessage }}
        </div>
    </div>
</template>

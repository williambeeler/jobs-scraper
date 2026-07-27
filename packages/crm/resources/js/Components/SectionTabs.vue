<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    items: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <div class="williambeeler-card overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 px-4 py-4 sm:px-6">
            <Link
                v-for="item in items"
                :key="item.label"
                :href="item.href"
                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                :class="item.active
                    ? 'bg-slate-950 text-white shadow-sm'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
            >
                {{ item.label }}
            </Link>
        </div>

        <div class="px-4 py-5 sm:px-6">
            <slot />
        </div>
    </div>
</template>

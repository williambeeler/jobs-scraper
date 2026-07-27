<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-2">
        <Link
            v-for="link in links"
            :key="`${link.label}-${link.url}`"
            :href="link.url || '#'"
            class="min-w-10 rounded-full border px-3 py-2 text-center text-sm font-semibold transition"
            :class="[
                link.active
                    ? 'border-cyan-400 bg-cyan-400 text-slate-950'
                    : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:text-slate-900',
                !link.url ? 'pointer-events-none opacity-50' : '',
            ]"
            v-html="link.label"
        />
    </div>
</template>

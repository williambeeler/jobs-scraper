<script setup>
import ApplicationLogo from '@crm/Components/ApplicationLogo.vue'
import { Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
})

const mobileNavigationOpen = ref(false)

const navigationItems = computed(() => [
    {
        label: 'Companies',
        href: route('companies.index'),
        active: route().current('companies.*'),
    },
    {
        label: 'Saved Searches',
        href: route('saved-searches.index'),
        active: route().current('saved-searches.*'),
    },
    {
        label: 'My Business',
        href: route('my-business.index'),
        active: route().current('my-business.*'),
    },
    {
        label: 'Tools',
        href: route('tools.index'),
        active: route().current('tools.*') || route().current('statuses.*'),
    },
])
</script>

<template>
    <div class="min-h-screen bg-white">
        <div class="border-b border-slate-300 bg-gradient-to-br from-slate-400 via-slate-300 to-slate-200 text-slate-950">
            <nav aria-label="CRM navigation">
                <div class="w-full px-4 sm:px-6 xl:px-10 2xl:px-12">
                    <div class="flex min-h-[4.75rem] items-center justify-between gap-4">
                        <div class="flex items-center gap-8">
                            <Link :href="route('companies.index')" class="shrink-0">
                                <ApplicationLogo />
                            </Link>

                            <div class="hidden items-center gap-8 lg:flex">
                                <Link
                                    v-for="item in navigationItems"
                                    :key="item.label"
                                    :href="item.href"
                                    class="border-b-2 px-1 py-6 text-sm font-semibold tracking-[0.02em] transition"
                                    :class="item.active
                                        ? 'border-slate-900 text-slate-950'
                                        : 'border-transparent text-slate-800 hover:border-slate-500 hover:text-slate-950'"
                                >
                                    {{ item.label }}
                                </Link>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-300 bg-slate-100/80 text-slate-950 lg:hidden"
                            @click="mobileNavigationOpen = !mobileNavigationOpen"
                        >
                            <span class="sr-only">Toggle CRM navigation</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 5.75A.75.75 0 013.75 5h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 5.75zm0 4.25a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75A.75.75 0 013 10zm0 4.25a.75.75 0 01.75-.75h12.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-if="mobileNavigationOpen" class="border-t border-slate-300/80 lg:hidden">
                    <div class="space-y-1 px-4 py-4 sm:px-6">
                        <Link
                            v-for="item in navigationItems"
                            :key="item.label"
                            :href="item.href"
                            class="block rounded-xl px-4 py-3 text-sm font-semibold transition"
                            :class="item.active ? 'bg-slate-100/90 text-slate-950 shadow-sm' : 'text-slate-900 hover:bg-slate-200/70'"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </nav>

            <header class="px-4 pb-8 pt-7 sm:px-6 xl:px-10 2xl:px-12">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h1 class="text-4xl font-normal leading-tight text-slate-950">{{ props.title }}</h1>
                        <p v-if="props.subtitle" class="mt-3 max-w-2xl text-base text-slate-700">{{ props.subtitle }}</p>
                    </div>
                    <div v-if="$slots.actions" class="shrink-0">
                        <slot name="actions" />
                    </div>
                </div>
            </header>
        </div>

        <main class="bg-white pb-12 pt-8">
            <div class="w-full px-4 sm:px-6 xl:px-10 2xl:px-12">
                <slot />
            </div>
        </main>
    </div>
</template>

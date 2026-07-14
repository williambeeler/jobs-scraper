<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { reactive } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';

type Listing = {
    id: number;
    source: string;
    title: string;
    company: string | null;
    location: string | null;
    url: string;
    posted_at: string | null;
    posted_relative: string | null;
    salary: string | null;
    last_seen_at: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    listings: {
        data: Listing[];
        links: PaginationLink[];
        total: number;
    };
    filters: { search: string; source: string };
    sources: string[];
}>();

const filters = reactive({ ...props.filters });

function submit(): void {
    router.get('/job-listings', filters, {
        preserveState: true,
        replace: true,
    });
}

function paginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Job listings', href: '/job-listings' }],
    },
});
</script>

<template>
    <Head title="Job listings" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Job listings</h1>
            <p class="text-sm text-muted-foreground">
                {{ listings.total }} listings collected by the scraper
            </p>
        </div>

        <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submit">
            <div class="relative flex-1">
                <Search class="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                <Input
                    v-model="filters.search"
                    class="pl-9"
                    placeholder="Search title, company, or location"
                />
            </div>
            <select
                v-model="filters.source"
                class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
            >
                <option value="">All sources</option>
                <option v-for="source in sources" :key="source" :value="source">
                    {{ source }}
                </option>
            </select>
            <Button type="submit">Filter</Button>
        </form>

        <div v-if="listings.data.length" class="grid gap-4 lg:grid-cols-2">
            <Link
                v-for="listing in listings.data"
                :key="listing.id"
                :href="`/job-listings/${listing.id}`"
                class="rounded-xl border bg-card p-5 transition-colors hover:bg-accent/40"
            >
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold leading-snug">{{ listing.title }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ listing.company || 'Unknown company' }}
                        </p>
                    </div>
                    <Badge variant="secondary">{{ listing.source }}</Badge>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                    <span>{{ listing.location || 'Location not provided' }}</span>
                    <span v-if="listing.salary">{{ listing.salary }}</span>
                    <span>{{ listing.posted_relative || listing.posted_at || 'Date unknown' }}</span>
                </div>
            </Link>
        </div>

        <div v-else class="rounded-xl border border-dashed p-12 text-center text-muted-foreground">
            No stored job listings match these filters.
        </div>

        <nav v-if="listings.links.length > 3" class="flex flex-wrap justify-center gap-2">
            <template v-for="link in listings.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'"
                    preserve-state
                >{{ paginationLabel(link.label) }}</Link>
                <span v-else class="rounded-md border px-3 py-1.5 text-sm opacity-40">
                    {{ paginationLabel(link.label) }}
                </span>
            </template>
        </nav>
    </div>
</template>

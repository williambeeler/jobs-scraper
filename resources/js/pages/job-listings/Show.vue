<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from '@lucide/vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';

defineProps<{
    listing: {
        id: number;
        source: string;
        title: string;
        company: string | null;
        location: string | null;
        url: string;
        posted_at: string | null;
        posted_age: string | null;
        salary: string | null;
        first_seen_at: string;
        last_seen_at: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Job listings', href: '/job-listings' },
            { title: 'Listing details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="listing.title" />

    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Link
            href="/job-listings"
            class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Back to listings
        </Link>

        <article class="rounded-xl border bg-card p-6 md:p-8">
            <div
                class="flex flex-col items-start justify-between gap-4 sm:flex-row"
            >
                <div>
                    <Badge variant="secondary" class="mb-3">{{
                        listing.source
                    }}</Badge>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ listing.title }}
                    </h1>
                    <p class="mt-2 text-lg text-muted-foreground">
                        {{ listing.company || 'Unknown company' }}
                    </p>
                </div>
                <Button as-child>
                    <a
                        :href="listing.url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        View original
                        <ExternalLink class="ml-2 size-4" />
                    </a>
                </Button>
            </div>

            <dl class="mt-8 grid gap-6 border-t pt-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">
                        Location
                    </dt>
                    <dd class="mt-1">
                        {{ listing.location || 'Not provided' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">
                        Posted
                    </dt>
                    <dd class="mt-1">
                        {{ listing.posted_age || 'Unknown' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">
                        Salary
                    </dt>
                    <dd class="mt-1">{{ listing.salary || 'Not provided' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-muted-foreground">
                        Last seen
                    </dt>
                    <dd class="mt-1">
                        {{ new Date(listing.last_seen_at).toLocaleString() }}
                    </dd>
                </div>
            </dl>
        </article>
    </div>
</template>

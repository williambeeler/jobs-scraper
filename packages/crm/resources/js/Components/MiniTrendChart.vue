<script setup>
import { computed } from 'vue'

const props = defineProps({
    labels: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    strokeColor: {
        type: String,
        default: '#12d6cc',
    },
    fillColor: {
        type: String,
        default: 'rgba(18, 214, 204, 0.12)',
    },
})

const width = 320
const height = 120
const padding = 12

const points = computed(() => {
    const numericValues = props.values.map((value) => Number(value || 0))
    const max = Math.max(...numericValues, 0)
    const min = Math.min(...numericValues, 0)
    const range = max - min || 1

    return numericValues.map((value, index) => {
        const x = padding + ((width - padding * 2) / Math.max(numericValues.length - 1, 1)) * index
        const y = height - padding - (((value - min) / range) * (height - padding * 2))

        return { x, y }
    })
})

const linePoints = computed(() => points.value.map((point) => `${point.x},${point.y}`).join(' '))

const areaPoints = computed(() => {
    if (points.value.length === 0) {
        return ''
    }

    const firstPoint = points.value[0]
    const lastPoint = points.value[points.value.length - 1]

    return [
        `${firstPoint.x},${height - padding}`,
        ...points.value.map((point) => `${point.x},${point.y}`),
        `${lastPoint.x},${height - padding}`,
    ].join(' ')
})
</script>

<template>
    <div class="space-y-3">
        <svg
            v-if="points.length > 0"
            :viewBox="`0 0 ${width} ${height}`"
            class="h-32 w-full overflow-visible"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <line
                :x1="padding"
                :y1="height - padding"
                :x2="width - padding"
                :y2="height - padding"
                stroke="rgba(148, 163, 184, 0.35)"
                stroke-width="1"
            />
            <polygon :points="areaPoints" :fill="fillColor" />
            <polyline
                :points="linePoints"
                fill="none"
                :stroke="strokeColor"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>

        <div class="grid grid-cols-4 gap-2 text-[11px] font-medium text-slate-400 sm:grid-cols-6">
            <span
                v-for="label in labels.slice(Math.max(labels.length - 6, 0))"
                :key="label"
                class="truncate"
            >
                {{ label }}
            </span>
        </div>
    </div>
</template>

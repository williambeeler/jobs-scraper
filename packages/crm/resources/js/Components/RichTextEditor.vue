<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    inputId: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = ref(null)

function handleChange(event) {
    emit('update:modelValue', event.target.value)
}

function preventFileAttachment(event) {
    event.preventDefault()
}

onMounted(() => {
    if (! editor.value) {
        return
    }

    editor.value.addEventListener('trix-change', handleChange)
    editor.value.addEventListener('trix-file-accept', preventFileAttachment)

    if (props.modelValue) {
        editor.value.editor.loadHTML(props.modelValue)
    }
})

onBeforeUnmount(() => {
    if (! editor.value) {
        return
    }

    editor.value.removeEventListener('trix-change', handleChange)
    editor.value.removeEventListener('trix-file-accept', preventFileAttachment)
})

watch(
    () => props.modelValue,
    (value) => {
        if (! editor.value || ! editor.value.editor) {
            return
        }

        if (editor.value.value === value) {
            return
        }

        editor.value.editor.loadHTML(value || '')
    },
)
</script>

<template>
    <div class="mt-2">
        <input :id="inputId" type="hidden" :value="modelValue">
        <trix-editor
            ref="editor"
            :input="inputId"
            :placeholder="placeholder"
        />
    </div>
</template>

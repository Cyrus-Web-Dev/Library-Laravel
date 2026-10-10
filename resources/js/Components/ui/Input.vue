<script setup>
import { cn } from '@/lib/utils';
defineProps({
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    placeholder: String,
    disabled: Boolean,
    error: String,
    class: String,
});
const emit = defineEmits(['update:modelValue']);
</script>
<template>
    <div class="w-full">
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="cn(
                'flex h-10 w-full rounded-xl border bg-white px-3.5 py-2 text-sm transition-colors placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-brand/30 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-stone-900 dark:placeholder:text-stone-500',
                error ? 'border-rose-400 focus:ring-rose-300' : 'border-stone-200 focus:border-brand dark:border-stone-700',
                $props.class
            )"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="mt-1.5 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>

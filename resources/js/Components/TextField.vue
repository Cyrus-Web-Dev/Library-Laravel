<script setup>
defineProps({
    modelValue: [String, Number],
    label: String,
    type: { type: String, default: 'text' },
    error: String,
    required: Boolean,
    autocomplete: String,
    hint: String,
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">
            {{ label }} <span v-if="required" class="text-rose-500">*</span>
        </label>
        <input
            :type="type"
            :value="modelValue"
            :autocomplete="autocomplete"
            class="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 dark:border-stone-700 dark:bg-stone-900 dark:text-white"
            :class="{ 'border-rose-400': error }"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="hint && !error" class="mt-1 text-xs text-stone-500">{{ hint }}</p>
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>

<script setup>
defineProps({ loading: Boolean, type: { type: String, default: 'submit' }, block: { type: Boolean, default: true } });
</script>

<template>
    <button
        :type="type"
        :disabled="loading"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-light disabled:opacity-60"
        :class="block ? 'w-full' : 'w-auto'"
    >
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <slot />
    </button>
</template>

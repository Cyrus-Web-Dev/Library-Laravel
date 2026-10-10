<script setup>
import { cn } from '@/lib/utils';
import { computed } from 'vue';
const props = defineProps({
    variant: { type: String, default: 'default' }, // default | success | warning | danger | info | muted
    class: String,
});
const classes = computed(() =>
    cn(
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition',
        {
            'bg-brand/10 text-brand dark:bg-brand/20 dark:text-emerald-300': props.variant === 'default',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': props.variant === 'success',
            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': props.variant === 'warning',
            'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300': props.variant === 'danger',
            'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300': props.variant === 'info',
            'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300': props.variant === 'muted',
        },
        props.class
    )
);
</script>
<template>
    <span :class="classes"><slot /></span>
</template>

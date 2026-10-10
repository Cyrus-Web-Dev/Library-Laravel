<script setup>
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'default' }, // default | secondary | outline | ghost | danger | gold
    size: { type: String, default: 'default' }, // sm | default | lg | icon
    as: { type: String, default: 'button' },
    disabled: Boolean,
    loading: Boolean,
    class: String,
});

const classes = computed(() =>
    cn(
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl font-semibold transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 disabled:pointer-events-none disabled:opacity-50',
        {
            'bg-brand text-white shadow-md shadow-brand/20 hover:bg-brand-light hover:shadow-brand/30 active:scale-[0.98]': props.variant === 'default',
            'bg-stone-100 text-stone-900 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700': props.variant === 'secondary',
            'border border-stone-200 bg-white/80 text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-900/80 dark:text-stone-200 dark:hover:bg-stone-800': props.variant === 'outline',
            'text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-100': props.variant === 'ghost',
            'bg-rose-600 text-white shadow-md shadow-rose-600/20 hover:bg-rose-700': props.variant === 'danger',
            'bg-gold text-[#3b2a00] shadow-md shadow-gold/25 hover:brightness-105': props.variant === 'gold',
        },
        {
            'h-8 px-3 text-xs': props.size === 'sm',
            'h-10 px-5 text-sm': props.size === 'default',
            'h-12 px-7 text-base': props.size === 'lg',
            'h-10 w-10': props.size === 'icon',
        },
        props.class
    )
);
</script>

<template>
    <component :is="as" :class="classes" :disabled="disabled || loading">
        <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <slot />
    </component>
</template>

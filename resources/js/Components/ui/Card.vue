<script setup>
import { cn } from '@/lib/utils';
defineProps({ class: String });
</script>
<template>
    <div :class="cn('rounded-2xl border border-stone-200/80 bg-white shadow-sm dark:border-stone-800/80 dark:bg-stone-900/80', $props.class)">
        <slot />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let counter = 0;

const tones = {
    success: 'border-emerald-500 text-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300',
    error: 'border-rose-500 text-rose-800 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300',
    danger: 'border-rose-500 text-rose-800 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-300',
    warning: 'border-amber-500 text-amber-800 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300',
    info: 'border-sky-500 text-sky-800 bg-sky-50 dark:bg-sky-950/40 dark:text-sky-300',
};

const flash = computed(() => page.props.flash || {});

watch(flash, (val) => {
    for (const type of ['success', 'error', 'danger', 'warning', 'info']) {
        if (val[type]) {
            const id = ++counter;
            toasts.value.push({ id, type: type === 'error' ? 'danger' : type, message: val[type] });
            setTimeout(() => dismiss(id), 6000);
        }
    }
}, { immediate: true, deep: true });

function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
}
</script>

<template>
    <div class="fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-x-8 opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="t in toasts"
                :key="t.id"
                class="flex items-start gap-3 rounded-xl border-l-4 bg-white/95 px-4 py-3 shadow-lg backdrop-blur dark:bg-stone-900/95"
                :class="tones[t.type]"
            >
                <p class="flex-1 text-sm font-medium">{{ t.message }}</p>
                <button type="button" class="text-stone-400 hover:text-stone-600" @click="dismiss(t.id)">✕</button>
            </div>
        </TransitionGroup>
    </div>
</template>

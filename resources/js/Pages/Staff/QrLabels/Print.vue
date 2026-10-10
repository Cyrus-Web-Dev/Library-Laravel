<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

defineProps({ labels: Array });

onMounted(() => {
    setTimeout(() => window.print(), 400);
});
</script>

<template>
    <Head title="Cetak Label QR" />
    <div class="min-h-screen bg-white p-6 print:p-2">
        <div class="mb-4 flex items-center justify-between print:hidden">
            <p class="text-sm text-stone-600">{{ labels.length }} label siap dicetak</p>
            <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white" @click="window.print()">Cetak Sekarang</button>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 print:grid-cols-3 print:gap-3">
            <div
                v-for="l in labels"
                :key="l.id"
                class="flex flex-col items-center rounded-xl border border-stone-200 p-3 text-center"
            >
                <div class="mb-2" v-html="l.qr_svg"></div>
                <p class="text-[10px] font-mono text-stone-500">{{ l.code }}</p>
                <p class="line-clamp-2 text-xs font-semibold text-stone-900">{{ l.title }}</p>
                <p class="text-[10px] text-stone-500">{{ l.author }}</p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body { background: white !important; }
    .print\:hidden { display: none !important; }
}
</style>

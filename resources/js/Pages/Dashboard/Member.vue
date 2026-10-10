<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { BookOpen, Search, Clock } from 'lucide-vue-next';

defineProps({ active: Object });

const toneClass = {
    ok: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    warn: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    danger: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    info: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    muted: 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300',
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard Anggota">
        <div class="mb-6 grid gap-4 sm:grid-cols-2">
            <Link :href="route('catalog.index')" class="group flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-brand/30 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                <div class="grid h-12 w-12 place-items-center rounded-xl bg-brand/10 text-brand transition group-hover:bg-brand group-hover:text-white">
                    <Search class="h-6 w-6" />
                </div>
                <div>
                    <p class="font-semibold text-stone-900 dark:text-white">Jelajahi Katalog</p>
                    <p class="text-sm text-stone-500">Cari dan ajukan pinjam buku</p>
                </div>
            </Link>
            <div class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="grid h-12 w-12 place-items-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
                    <Clock class="h-6 w-6" />
                </div>
                <div>
                    <p class="font-semibold text-stone-900 dark:text-white">{{ active?.data?.length || 0 }} aktif</p>
                    <p class="text-sm text-stone-500">Sedang dipinjam / diajukan</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="border-b border-stone-200 px-5 py-4 dark:border-stone-800">
                <h2 class="font-display text-lg font-semibold text-stone-900 dark:text-white">Sedang dipinjam / diajukan</h2>
            </div>

            <div v-if="!active?.data?.length" class="p-12 text-center">
                <BookOpen class="mx-auto h-12 w-12 text-stone-300 dark:text-stone-600" />
                <p class="mt-3 text-stone-500">Belum ada buku yang dipinjam.</p>
                <Link :href="route('catalog.index')" class="mt-4 inline-block text-sm font-medium text-brand hover:underline">
                    Mulai jelajahi katalog →
                </Link>
            </div>

            <ul v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                <li v-for="loan in active.data" :key="loan.id" class="flex items-center gap-4 px-5 py-4">
                    <img v-if="loan.book?.cover_url" :src="loan.book.cover_url" class="h-14 w-10 rounded-lg object-cover" alt="">
                    <div v-else class="grid h-14 w-10 place-items-center rounded-lg bg-brand/10 text-brand">
                        <BookOpen class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-stone-900 dark:text-white">{{ loan.book?.title }}</p>
                        <p class="text-sm text-stone-500">{{ loan.book?.author }} · Jatuh tempo {{ loan.due_date }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold" :class="toneClass[loan.status_tone] || toneClass.muted">
                        {{ loan.status_label }}
                    </span>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
